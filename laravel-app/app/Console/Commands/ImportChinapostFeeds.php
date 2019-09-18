<?php

namespace App\Console\Commands;

use App\Models\WordpressFeed;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportChinapostFeeds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:ChinapostXML';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import Chinapost xml';

    protected $feedsSource = 'chinapost';
    protected $authorId = 27;
    protected $categoryIds = '491';
    protected $publishStatus = 'publish';
    protected $wordpressRootPath = '/var/vhosts/4wayvoice.nownews.com';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $feedsUrl = 'https://chinapost.nownews.com/category/news/taiwan-news/taiwan-foreigners/feed';

        // get xml info
        $result = file_get_contents($feedsUrl);

        // turn xml string into SimpleXMLElement
        $this->info("Download xml from {$feedsUrl}...");
        $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (!$xml) {
            $this->error('Invalid xml');
            exit;
        }

        $feeds = $xml->channel->item;

        foreach ($feeds as $feed) {
            $postId = (int)$feed->{'post-id'};
            $postTitle = (string)$feed->title;
            $postDate =  Carbon::parse($feed->pubDate, 'Asia/Taipei')->toDateTimeString();
            $postContent = (string)$feed->children('content', true);

            // ignore if post exists
            if ($this->isPostExists($this->feedsSource, $postId)) {
                continue;
            }

            // import wordpress post
            $wpCli = "wp post create --allow-root --path=\"{$this->wordpressRootPath}\" --post_type=post --post_author={$this->authorId} --post_category={$this->categoryIds} --post_date=\"{$postDate}\" --post_title=\"" . htmlspecialchars($postTitle, ENT_QUOTES) . "\" --post_status=\"{$this->publishStatus}\" --post_content=\"{$postContent}\" --porcelain";
            $wpPostId = (int)shell_exec($wpCli);

            // record post_id to prevent repeat import
            $this->recordPost($this->feedsSource, $postId);

            // print messages
            $this->line("Import Completed ! wp_post_id: {$wpPostId}, title: {$postTitle}");
        }
    }

    protected function isPostExists(string $source, string $postId)
    {
        $wordpressFeed = new WordpressFeed;
        $wordpressFeed = $wordpressFeed
            ->where([
                'source' => $source,
                'post_id' => $postId,
            ])
            ->first();

        if (!$wordpressFeed) {
            return false;
        }

        $this->warn("The post is exists, source: {$source}, post_id: {$postId}");

        return true;
    }

    protected function recordPost(string $source, string $postId): void
    {
        WordpressFeed::create([
            'source' => $source,
            'post_id' => $postId,
        ]);
    }
}
