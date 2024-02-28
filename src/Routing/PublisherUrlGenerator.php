<?php

namespace Plank\Publisher\Routing;

use Illuminate\Routing\UrlGenerator;
use Plank\Publisher\Facades\Publisher;

class PublisherUrlGenerator extends UrlGenerator
{
    public function to($path, $extra = [], $secure = null)
    {
        $url = parent::to($path, $extra, $secure);

        if (Publisher::draftContentAllowed()) {
            $url = $this->appendQueryParameter($url, config()->get('publisher.urls.previewKey'), 'true');
        }

        return $url;
    }

    protected function appendQueryParameter($url, $key, $value)
    {
        $query = parse_url($url, PHP_URL_QUERY);

        if ($query) {
            $url .= '&'.$key.'='.$value;
        } else {
            $url .= '?'.$key.'='.$value;
        }

        return $url;
    }
}
