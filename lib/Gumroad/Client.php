<?php

namespace Gumroad;

/**
 * Gumroad API Client
 *
 * @package Gumroad
 * @author  Kazunori Ninomiya <kazunori.ninomiya@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class Client
{
    const END_POINT = 'https://gumroad.com/api/v1';

    public  $token;
    public  $endpoint;
    private $timeout;

    public function __construct()
    {
        $this->token    = null;
        $this->endpoint = self::END_POINT;
        $this->timeout  = 2000;
    }

    public function getAuthenticateUrl()
    {
        return $this->endpoint . '/sessions';
    }

    public function getLinkUrl($id = '')
    {
        $url = $this->endpoint . '/links';
        if (strlen($id) > 0) {
            $url .= '/' . $id;
        }
        return $url;
    }

    public function setTimeout($timeout)
    {
        if (is_numeric($timeout)) {
            $this->timeout = $timeout < 0 ? 1 : (int)$timeout;
        }
        return $this;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function authenticate($email, $password)
    {
        $params = array('email'    => $email,
                        'password' => $password);
        $url = $this->getAuthenticateUrl();
        $response = $this->_request('POST', $url, $params);
        $this->token = $response->token;

        return $this;
    }

    public function deauthenticate()
    {
        $url = $this->endpoint . '/sessions';
        $this->_request('DELETE', $url);
        $this->token = null;

        return $this;
    }

    public function addLink(Link $link)
    {
        $params = array('name'        => $link->name,
                        'url'         => $link->url,
                        'price'       => $link->price,
                        'description' => $link->description);
        $url = $this->getLinkUrl();
        $response = $this->_request('POST', $url, $params);

        $link->id          = $response->link->id;
        $link->name        = $response->link->name;
        $link->url         = $response->link->url;
        $link->price       = $response->link->price;
        $link->description = $response->link->description;
        $link->currency    = $response->link->currency;
        $link->shortUrl    = $response->link->short_url;
        $link->views       = $response->link->views;
        $link->previewUrl  = $response->link->preview_url;
        $link->purchases   = $response->link->purchases;
        $link->balance     = $response->link->balance;

        return $this;
    }

    public function editLink(Link $link)
    {
        $params = array('name'        => $link->name,
                        'url'         => $link->url,
                        'price'       => $link->price,
                        'description' => $link->description);
        $url = $this->getLinkUrl($link->id);
        $this->_request('PUT', $url, $params);

        return $this;
    }

    public function deleteLink(Link $link)
    {
        $params = array('name'        => $link->name,
                        'url'         => $link->url,
                        'price'       => $link->price,
                        'description' => $link->description);
        $url = $this->getLinkUrl($link->id);
        $this->_request('DELETE', $url, $params);

        return $this;
    }

    public function getLink($id)
    {
        $url = $this->getLinkUrl($id);
        $response = $this->_request('GET', $url);
        return new Link(array(
            'id'          => $response->link->id,
            'name'        => $response->link->name,
            'url'         => $response->link->url,
            'price'       => $response->link->price,
            'description' => $response->link->description,
            'currency'    => $response->link->currency,
            'shortUrl'    => $response->link->short_url,
            'views'       => $response->link->views,
            'previewUrl'  => $response->link->preview_url,
            'purchases'   => $response->link->purchases,
            'balance'     => $response->link->balance
        ));
    }

    public function getLinks()
    {
        $url = $this->getLinkUrl();
        $response = $this->_request('GET', $url);

        $links = array();
        foreach ($response->links as $link) {
            $links[] = new Link(array(
                'id'          => $link->id,
                'name'        => $link->name,
                'url'         => $link->url,
                'price'       => $link->price,
                'description' => $link->description,
                'currency'    => $link->currency,
                'shortUrl'    => $link->short_url,
                'views'       => $link->views,
                'previewUrl'  => $link->preview_url,
                'purchases'   => $link->purchases,
                'balance'     => $link->balance
            ));
        }
        return $links;
    }

    private function _request($method, $url, array $params = array())
    {
        $data = http_build_query($params);
        $ch   = curl_init();

        switch (strtoupper($method)) {
            case 'GET':
                if ($data != '') {
                    $url = $url . '?' . $data;
                }
                break;

            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;

            case 'PUT':
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;

            default:
                break;
        }

        if ($this->token !== null) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->token . ':');
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->timeout);
        $response = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (preg_match('/\A(404|403|500)\z/', $code)) {
            throw new Exception('Network Error');
        }

        $error = curl_error($ch);
        if ($error) {
            throw new Exception($error);
        }

        $response = @json_decode($response);
        if (!$response) {
            throw new Exception('Network Error');
        }
        else if (!$response->success) {
            $message = 'Unknown Error';
            if (isset($response->error->message)) {
                $message = $response->error->message;
            }
            else if (isset($response->message)) {
                $message = $response->message;
            }
            throw new Exception($message);
        }

        return $response;
    }
}
