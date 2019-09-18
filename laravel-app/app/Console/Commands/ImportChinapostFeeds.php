<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

        $posts = [];
        foreach ($feeds as $feed) {
            $postId = $feed->{'post-id'};
            $title = $feed->title;
            $pubDate = $feed->pubDate;
            $content = $feed->children('content', true);
            $posts[] = [
                'postId' => (int)$postId,
                'title' => (string)$title,
                'pubDate' => Carbon::parse($pubDate, 'Asia/Taipei')->toDateTimeString(),
                'content' => (string)$content,
            ];
        }
    }

    protected function isPostsExists(string $guid)
    {
        $post = DB::table('cna_feed')
            ->where('guid', $guid)
            ->first();

        if (!$post) {
            return false;
        }

        $this->warn("Duid is exists: {$guid}");

        return true;
    }

    protected function deletePostRecord(string $guid)
    {
        DB::table('cna_feed')
            ->where('guid', $guid)
            ->delete();
    }
}
