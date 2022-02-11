<?php

declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Util to grab a facebook profile picture from userAlias.
 */
class FacebookPictureFinder
{
    protected string $facebookUserAlias;
    protected ResponseInterface $response;

    /**
     * @param string $facebookUserAlias
     */
    public function __construct(string $facebookUserAlias)
    {
        $this->facebookUserAlias = $facebookUserAlias;
    }

    /**
     * @return string Facebook profile image URL or FALSE
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPictureUrl(): string
    {
        $client = new Client();
        $this->response = $client->get('http://graph.facebook.com/' . $this->facebookUserAlias . '/picture?redirect=false&width=200&height=200');
        $json = json_decode($this->response->getBody()->getContents(), true);
        return $json['data']['url'];
    }
}
