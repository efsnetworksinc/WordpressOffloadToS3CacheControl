<?php

/*
Plugin Name: Offload To S3 Cache Control
Plugin URI: https://github.com/efsnetworksinc/WordpressOffloadToS3CacheControl
Description: This plugin is an add-on to the "Offload to S3" wordpress plugin that prevents the site from reloading caching, please see README for installation details.
Version: 1.0
Author: Max BrownGold
*/

namespace EFSNetworks\OTS3CC;

/*
 * @param array        $attr       Attributes for the image markup.
 * @param WP_Post      $attachment Image attachment post.
 * @param string|array $size       Requested size. Image size or array of width and height values
 *                                 (in that order). Default 'thumbnail'.
 */
function cache_control_thumbnails($attr, $attachment, $size = 'thumbnail')
{

    if (!$attr['src'] ?? true) {
        return $attr;
    }

    global $wpdb;
    $cacheVar = $wpdb->get_var(
        $wpdb->prepare("
          SELECT MAX(meta_id)
          FROM {$wpdb->postmeta}
          WHERE post_id=%d
           AND meta_key = 'amazonS3_info'",
            $attachment->ID)
    );

    if (!empty($cacheVar) && is_numeric($cacheVar)) {
        $parsedURL = parse_url($attr['src']);
        $query = $parsedURL['query'];
        $query = explode('&', $query);
        foreach ($query as $index=>$pair) {
            $split = explode('=', $pair);
            $query[$split[0]] = $query[$split[1]];
            unset($query[$index]);
        }

        $query['cacheControl'] = $cacheVar;

        $parsedURL['query'] = http_build_query($query);
        $attr['src'] = unparse_url($parsedURL);
    }



    return $attr;
}

add_filter('wp_get_attachment_image_attributes', __NAMESPACE__ . 'cache_control_thumbnails', 20, 3);

/**
 * Accepts an array formatted like the output of parse_url() and rebuilds the url that was used to generate that output
 * @url http://php.net/manual/en/function.parse-url.php#106731
 * @param array $parsed_url
 *
 * @return string
 */
function unparse_url($parsed_url) {
    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
    $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
    $pass     = ($user || $pass) ? "$pass@" : '';
    $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return "$scheme$user$pass$host$port$path$query$fragment";
}