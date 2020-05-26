<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

use GuzzleHttp\Client;

/**
 * Util to grab a facebook profile picture from userAlias.
 */
class FacebookPictureFinder
{
    /**
     * @var string
     */
    protected $facebookUserAlias;
    protected $response;

    /**
     * @param string $facebookUserAlias
     */
    public function __construct($facebookUserAlias)
    {
        $this->facebookUserAlias = $facebookUserAlias;
    }

    /**
     * @return string Facebook profile image URL or FALSE
     */
    public function getPictureUrl()
    {
        $client = new Client();
        $this->response = $client->get('http://graph.facebook.com/'.$this->facebookUserAlias.'/picture?redirect=false&width=200&height=200');
        $json = json_decode($this->response->getBody()->getContents(), true);
        return $json['data']['url'];
    }
}
