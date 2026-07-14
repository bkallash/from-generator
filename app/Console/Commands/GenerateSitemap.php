<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the XML sitemap for the website.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating sitemap...');
        
        $url = config('app.url', 'https://formgenerator.me');
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }
        $baseUrl = rtrim($url, '/');
        
        $pagesXml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $pagesXml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . PHP_EOL;
        
        $pages = [
            ['url' => $baseUrl . '/', 'freq' => 'daily', 'priority' => '1.0'],
            ['url' => $baseUrl . '/login', 'freq' => 'weekly', 'priority' => '0.8'],
            ['url' => $baseUrl . '/register', 'freq' => 'weekly', 'priority' => '0.8'],
            ['url' => $baseUrl . '/dashboard', 'freq' => 'weekly', 'priority' => '0.7'],
            ['url' => $baseUrl . '/terms', 'freq' => 'monthly', 'priority' => '0.3'],
            ['url' => $baseUrl . '/privacy', 'freq' => 'monthly', 'priority' => '0.3'],
        ];

        foreach ($pages as $page) {
            $pagesXml .= '    <url>' . PHP_EOL;
            $pagesXml .= '        <loc>' . htmlspecialchars($page['url']) . '</loc>' . PHP_EOL;
            $pagesXml .= '        <changefreq>' . $page['freq'] . '</changefreq>' . PHP_EOL;
            $pagesXml .= '        <priority>' . $page['priority'] . '</priority>' . PHP_EOL;
            $pagesXml .= '    </url>' . PHP_EOL;
        }
        $pagesXml .= '</urlset>' . PHP_EOL;

        $sitemapPath = public_path('sitemap.xml');
        file_put_contents($sitemapPath, $pagesXml);
        
        // Clean up legacy multi-file sitemaps if they exist
        @unlink(public_path('sitemap-pages.xml'));
        @unlink(public_path('sitemap-forms.xml'));

        // Update/ensure sitemap directive in robots.txt
        $robotsPath = public_path('robots.txt');
        $sitemapLine = "Sitemap: {$baseUrl}/sitemap.xml";
        if (file_exists($robotsPath)) {
            $robotsContent = file_get_contents($robotsPath);
            if (preg_match('/^Sitemap:\s+/mi', $robotsContent)) {
                $robotsContent = preg_replace('/^Sitemap:\s+.*$/mi', $sitemapLine, $robotsContent);
            } else {
                $robotsContent = rtrim($robotsContent) . PHP_EOL . PHP_EOL . $sitemapLine . PHP_EOL;
            }
            file_put_contents($robotsPath, $robotsContent);
        } else {
            $newRobots = "User-agent: *" . PHP_EOL . "Disallow:" . PHP_EOL . PHP_EOL . $sitemapLine . PHP_EOL;
            file_put_contents($robotsPath, $newRobots);
        }
        
        $this->info("Sitemap generated successfully at {$sitemapPath}");
    }
}