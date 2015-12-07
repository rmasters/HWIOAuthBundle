<?php

namespace HWI\Bundle\OAuthBundle\OAuth\ResourceOwner;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AssemblaResourceOwner extends GenericOAuth2ResourceOwner
{
    protected $paths = [
        'identifier' => 'id',
        'nickname' => 'name',
        'realname' => 'name',
        'email' => 'email',
        'username' => 'username',
    ];

    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'authorization_url'         => 'https://api.assembla.com/authorization',
            'access_token_url'          => 'https://api.assembla.com/token',
            'infos_url'                 => 'https://api.assembla.com/v1/user.json',
            'use_bearer_authorization'  => true,
        ));
    }

    public function getAuthorizationUrl($redirectUri, array $extraParameters = array())
    {
        $url = parent::getAuthorizationUrl($redirectUri, $extraParameters);

        $url = parse_url($url);
        if (array_key_exists('query', $url)) {
            $query = [];
            foreach (explode('&', $url['query']) as $part) {
                $p = explode('=', $part, 2);
                $query[$p[0]] = $p[1];
            }

            if (array_key_exists('redirect_uri', $query)) {
                unset($query['redirect_uri']);
                $url['query'] = http_build_query($query);
            }
        }

        // Rebuild the URL
        return $this->buildUrl($url);
    }

    public function doGetTokenRequest($url, array $parameters = array())
    {
        $url = parse_url($url);
        $url['user'] = $parameters['client_id'];
        $url['pass'] = $parameters['client_secret'];
        $url['query'] = http_build_query(array_intersect_key($parameters, array_flip(['grant_type', 'code'])));
        $url = $this->buildUrl($url);

        return $this->httpRequest($url, '', [], 'POST');
    }

    private function buildUrl(array $parts)
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : 'http://';
        $url = implode('', [
            $scheme,
            isset($parts['user']) ? $parts['user'] . (isset($parts['pass']) ? ':' . $parts['pass'] : '') . '@' : '',
            $parts['host'],
            isset($parts['path']) ? $parts['path'] : '/',
            isset($parts['query']) ? '?' . $parts['query'] : '',
        ]);

        return $url;
    }
}
