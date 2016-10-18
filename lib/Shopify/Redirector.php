<?php

namespace Shopify;

interface Redirector
{

    /**
     * redirect to the supplied uri
     * @param string $uri
     * @return mixed
     */
    public function redirect($uri);

}
