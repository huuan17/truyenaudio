<?php

namespace App\Helpers;

use App\Models\Setting;

class SettingHelper
{
    /**
     * Get setting value
     */
    public static function get($key, $default = null)
    {
        return Setting::get($key, $default);
    }

    /**
     * Get SEO meta tags for homepage
     */
    public static function getHomeSeoTags()
    {
        return [
            'title' => self::get('seo_home_title', self::get('site_name', 'Audio Lara')),
            'description' => self::get('seo_home_description', self::get('site_description', '')),
            'keywords' => self::get('seo_home_keywords', self::get('site_keywords', '')),
        ];
    }

    /**
     * Get tracking codes for head section
     */
    public static function getHeadTrackingCodes()
    {
        $codes = [];

        // Google Tag Manager
        $gtmId = self::get('google_tag_manager_id');
        if (!empty($gtmId)) {
            $codes[] = "<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$gtmId}');</script>
<!-- End Google Tag Manager -->";
        }

        // Google Analytics
        $gaId = self::get('google_analytics_id');
        if (!empty($gaId)) {
            $codes[] = "<!-- Google Analytics -->
<script async src=\"https://www.googletagmanager.com/gtag/js?id={$gaId}\"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{$gaId}');
</script>
<!-- End Google Analytics -->";
        }

        // Facebook Pixel
        $fbPixelId = self::get('facebook_pixel_id');
        if (!empty($fbPixelId)) {
            $codes[] = "<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{$fbPixelId}');
fbq('track', 'PageView');
</script>
<noscript><img height=\"1\" width=\"1\" style=\"display:none\"
src=\"https://www.facebook.com/tr?id={$fbPixelId}&ev=PageView&noscript=1\"
/></noscript>
<!-- End Facebook Pixel Code -->";
        }

        // Custom head code
        $customHeadCode = self::get('custom_head_code');
        if (!empty($customHeadCode)) {
            $codes[] = $customHeadCode;
        }

        return implode("\n", $codes);
    }

    /**
     * Get tracking codes for body section
     */
    public static function getBodyTrackingCodes()
    {
        $codes = [];

        // Google Tag Manager (noscript)
        $gtmId = self::get('google_tag_manager_id');
        if (!empty($gtmId)) {
            $codes[] = "<!-- Google Tag Manager (noscript) -->
<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id={$gtmId}\"
height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->";
        }

        // Custom body code (remarketing, etc.)
        $customBodyCode = self::get('custom_body_code');
        if (!empty($customBodyCode)) {
            $codes[] = $customBodyCode;
        }

        return implode("\n", $codes);
    }

    /**
     * Get meta verification tags
     */
    public static function getMetaVerificationTags()
    {
        $tags = [];

        // Google Search Console
        $gscVerification = self::get('google_search_console_verification');
        if (!empty($gscVerification)) {
            $tags[] = "<meta name=\"google-site-verification\" content=\"{$gscVerification}\">";
        }

        return implode("\n", $tags);
    }

    /**
     * Get social media URLs
     */
    public static function getSocialUrls()
    {
        return [
            'facebook' => self::get('facebook_url'),
            'twitter' => self::get('twitter_url'),
            'youtube' => self::get('youtube_url'),
        ];
    }

    /**
     * Get site information
     */
    public static function getSiteInfo()
    {
        return [
            'name' => self::get('site_name', 'Audio Lara'),
            'description' => self::get('site_description', ''),
            'keywords' => self::get('site_keywords', ''),
        ];
    }
}
