<?php

namespace App\Console\Commands;

use App\Models\WordpressFeed;
use App\Models\IclTranslation;
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
    protected $postWpmlLanguage = 'en';
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
            $postDate = Carbon::parse($feed->pubDate, 'Asia/Taipei')->toDateTimeString();
            $postContent = html_entity_decode((string)$feed->children('content', true), ENT_QUOTES);

            preg_match('/(?:[\s\S]+)?(<img width="[\d]+" height="[\d]+" src="(https:\/\/[\s\S]+\.(?:jpg|png))".*attachment-thumbnail.* alt="(.*)".*\/>)(?:[\s\S]+)?/i', $postContent, $matches);
            $thumbnailImgTag = $matches[1];
            $thumbnailUrl = $matches[2];
            $thumbnailAlt = explode('"', $matches[3])[0];
//            $postContent = str_replace($thumbnailImgTag, '', $postContent);
            
            if ($postId === 743375) {
                dd($matches[3], $thumbnailAlt, $postContent);
            }

            // ignore if post exists
            if ($this->isPostExists($this->feedsSource, $postId)) {
                continue;
            }

            // import wordpress post
            $this->line('import wordpress post...');
            $wpCli = "wp post create --allow-root --path=\"{$this->wordpressRootPath}\" --post_type=post --post_author={$this->authorId} --post_category={$this->categoryIds} --post_date=\"{$postDate}\" --post_title=\"{$postTitle}\" --post_status=\"{$this->publishStatus}\" --post_content='{$postContent}' --porcelain";
            $wpPostId = (int)shell_exec($wpCli);
            $this->info("wp post id: {$wpPostId}");

            $iclTranslation = $this->switchPostWpmlLanguage($wpPostId, $this->postWpmlLanguage);

            // import image into media
            $this->line('import image into media...');
            $wpCli = "wp media import {$thumbnailUrl} --allow-root --path=\"{$this->wordpressRootPath}\" --user={$this->authorId} --title=\"{$postTitle}\" --caption=\"{$thumbnailAlt}\" --porcelain";
            $mediaId = (int)shell_exec($wpCli);
            $this->info("media id: {$mediaId}");

            // set post thumbnail
            $this->line('set post thumbnail...');
            $wpCli = "wp post meta update {$wpPostId} _thumbnail_id {$mediaId} --allow-root --path=\"{$this->wordpressRootPath}\"";
            shell_exec($wpCli);

            // record post_id to prevent repeat import
            $this->line('record post_id...');
            $this->recordPost($this->feedsSource, $postId, $wpPostId);

            // print messages
            $this->info("Import Completed ! wp_post_id: {$wpPostId}, title: {$postTitle}");
            $this->line('');
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

    protected function recordPost(string $source, string $postId, string $wpPostId): void
    {
        WordpressFeed::create([
            'source' => $source,
            'post_id' => $postId,
            'wp_post_id' => $wpPostId,
        ]);
    }

    protected function switchPostWpmlLanguage(int $postId, string $lang): ?IclTranslation
    {
        $iclTranslation = IclTranslation::where('element_id', $postId)->first();

        if (!$iclTranslation) {
            return $iclTranslation;
        }

        $iclTranslation->language_code = $lang;
        $iclTranslation->save();

        return $iclTranslation;
    }
}

