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

    const FEEDS_SOURCE = 'chinapost';

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
            $title = (string)$feed->title;
            $pubDate =  Carbon::parse($feed->pubDate, 'Asia/Taipei')->toDateTimeString();
            $content = (string)$feed->children('content', true);

            // ignore if post exists
            if ($this->isPostExists(self::FEEDS_SOURCE, $postId)) {
                continue;
            }

            // record post_id to prevent repeat import
            $this->recordPost(self::FEEDS_SOURCE, $postId);

            // print messages
            $this->line("Import Completed ! post_id: {$postId}, title: {$title}");
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
