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
    protected $signature = 'import:ChinapostXML {--feedUrl=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import Chinapost xml';

    protected $feedsSource = 'chinapost';
    protected $wordpressRootPath = '/var/vhosts/4wayvoice.nownews.com';

    const FEED_INFO = [
        [
            'url' => 'https://chinapost.nownews.com/category/news/taiwan-news/taiwan-foreigners/feed',
            'authorId' => 27,
            'categoryIds' => '491',
            'publishStatus' => 'publish',
            'postWpmlLanguage' => 'en',
        ],
        [
            'url' => 'https://chinapost.nownews.com/category/news/asia-news/feed',
            'authorId' => 27,
            'categoryIds' => '1112',
            'publishStatus' => 'publish',
            'postWpmlLanguage' => 'en',
        ],
        [
            'url' => 'https://chinapost.nownews.com/category/news/taiwan-news/taiwan-',
            'authorId' => 27,
            'categoryIds' => '1114',
            'publishStatus' => 'publish',
            'postWpmlLanguage' => 'en',
        ],
        [
            'url' => 'https://chinapost.nownews.com/category/news/bilingual-news/feed',
            'authorId' => 27,
            'categoryIds' => '1119,1156',
            'publishStatus' => 'publish',
            'postWpmlLanguage' => 'zh-hant',
        ],
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $feedUrl = $this->option('feedUrl');

        if (!isset($feedUrl)) {
            $this->error("Missing feedUrl option");
            exit;
        }

        $feedTarget = collect(self::FEED_INFO)
            ->first(function ($item) use ($feedUrl) {
                return $item['url'] === $feedUrl;
            });

        if (!isset($feedTarget)) {
            $this->error("Invalid feedUrl: {$feedUrl}");
            exit;
        }

        $this->importFeeds($feedTarget);
    }

    public function importFeeds(array $feedTarget): void
    {
        // get xml info
        $result = file_get_contents($feedTarget['url']);

        // turn xml string into SimpleXMLElement
        $this->info("Download xml from {$feedTarget['url']}...");
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

            // parse thumbnail image info
            preg_match('/(?:[\s\S]+)?(<img width="[\d]+" height="[\d]+" src="(https:\/\/[\s\S]+\.(?:jpg|png))".*attachment-thumbnail.* alt="(.*)".*\/>)(?:[\s\S]+)?/i', $postContent, $matches);
            $thumbnailImgTag = $matches[1];
            $thumbnailUrl = $matches[2];
            $thumbnailAlt = explode('"', $matches[3])[0];

            // remove thumbnail image in content
            $postContent = str_replace($thumbnailImgTag, '', $postContent);

            // ignore if post exists
            if ($this->isPostExists($this->feedsSource, $postId)) {
                continue;
            }

            // import wordpress post
            $this->line('import wordpress post...');
            $wpCli = "wp post create --allow-root --path=\"{$this->wordpressRootPath}\" --post_type=post --post_author={$feedTarget['authorId']} --post_category={$feedTarget['categoryIds']} --post_date=\"{$postDate}\" --post_title=\"{$postTitle}\" --post_status=\"{$feedTarget['publishStatus']}\" --post_content='{$postContent}' --porcelain";
            $wpPostId = (int)shell_exec($wpCli);
            $this->info("wp post id: {$wpPostId}");

            $iclTranslation = $this->switchPostWpmlLanguage($wpPostId, $feedTarget['postWpmlLanguage']);

            // import image into media
            $this->line('import image into media...');
            $wpCli = "wp media import {$thumbnailUrl} --allow-root --path=\"{$this->wordpressRootPath}\" --user={$feedTarget['authorId']} --title=\"{$postTitle}\" --caption=\"{$thumbnailAlt}\" --porcelain";
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
        $now = Carbon::now('Asia/Taipei');

        WordpressFeed::create([
            'source' => $source,
            'post_id' => $postId,
            'wp_post_id' => $wpPostId,
            'created_at' => $now,
            'updated_at' => $now,
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
