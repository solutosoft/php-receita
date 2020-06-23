<?php

namespace Solutosoft\Receita;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DomCrawler\Crawler;

class Client
{
    const URL_CAPTCHA = 'https://servicos.receita.fazenda.gov.br/Servicos/cnpjreva/captcha/gerarCaptcha.asp';
    const URL_REFER = 'https://servicos.receita.fazenda.gov.br/Servicos/cnpjreva/Cnpjreva_solicitacao3.asp';
    const URL_POST = 'https://servicos.receita.fazenda.gov.br/Servicos/cnpjreva/valida.asp';

    private $attributes =[
        'NOME EMPRESARIAL' => 'razao_social',
        'TÍTULO DO ESTABELECIMENTO (NOME DE FANTASIA)' => 'nome_fantasia',
        'CÓDIGO E DESCRIÇÃO DA ATIVIDADE ECONÔMICA PRINCIPAL' => 'cnae_principal',
        'CÓDIGO E DESCRIÇÃO DA NATUREZA JURÍDICA' => 'cnaes_secundario',
        'LOGRADOURO' => 'logradouro',
        'NÚMERO' => 'numero',
        'COMPLEMENTO' => 'complemento',
        'CEP' => 'cep',
        'BAIRRO/DISTRITO' => 'bairro',
        'MUNICÍPIO' => 'cidade',
        'UF' => 'uf',
        'SITUAÇÃO CADASTRAL' => 'situacao_cadastral',
        'DATA DA SITUAÇÃO CADASTRAL' => 'situacao_cadastral_data',
        'DATA DA SITUAÇÃO ESPECIAL' => 'situacao_especial',
        'TELEFONE' => 'telefone',
        'ENDEREÇO ELETRÔNICO' =>'email',
        'ENTE FEDERATIVO RESPONSÁVEL (EFR)' => 'responsavel',
        'DATA DE ABERTURA' => 'data_abertura'
    ];

    /**
     * @var array response cookies
     */
    private $cookies = [];

    /**
     * @var array $config Client configuration settings.
     */
    private $config;

    /**
     * The http client configuration settings
     *
     * @param array $config Client configuration settings.
     *
     * @see http://docs.guzzlephp.org/en/latest/request-options.html
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * The Captcha image
     * @return mixed
     */
    public function getCaptcha()
    {
        $client = $this->createClient();
        $response = $client->get(self::URL_CAPTCHA);

        if ($response->getStatusCode() === 200) {
            $this->cookies = $this->encodeCookies($client->getConfig('cookies')->toArray());
            return $response->getBody()->getContents();
        }

        return null;
    }

    /**
     * Captcha response cookies
     * @return array
     */
    public function getCookies()
    {
       return $this->cookies;
    }

    /**
     * @param string $cnpj
     * @param string $captcha
     * @return array
     */
    public function findByCNPJ($cnpj, $captcha, $cookies)
    {
        $i = 0;
        $client = $this->createClient();

        do {
            $result = [];
            $response = $client->post(self::URL_POST, [
                'cookies' => CookieJar::fromArray($cookies, 'servicos.receita.fazenda.gov.br'),
                'form_params' =>  [
                    'cnpj' => $cnpj,
                    'origem' => 'comprovante',
                    'search_type' => 'cnpj',
                    'submit1' => 'Consultar',
                    'txtTexto_captcha_serpro_gov_br' => $captcha,
                ]
            ]);

            if ($response->getStatusCode() == 200) {
                $contents = $response->getBody()->getContents();
                if (preg_match_all('/<table border="0" width="100%" style="\s+BORDER-COLLAPSE: collapse;\s+">.*?<\/table>/ism', $contents , $matches)) {
                    foreach($matches[0] as $html) {
                        $result = array_merge($result, $this->parseCNPJ($html));
                    }
                }
            }
        } while (empty($result) && $i < 2);

        return $result;
    }

    /**
     * Parses html content
     * @param string $content
     * @return array
     */
    protected function parseCNPJ($content)
    {
        $result = [];
        $crawler = new Crawler($content);

        foreach ($crawler->filter('td') as $td) {
            $td = new Crawler($td);
            $info = $td->filter('font:nth-child(1)');

            if ($info->count() > 0) {
                $key = trim(strip_tags(preg_replace('/\s+/', ' ', $info->html())));
                $attr =  isset($this->attributes[$key]) ? $this->attributes[$key] : null;

                if ($attr === null) {
                    continue;
                }

                $bs = $td->filter('font > b');
                foreach ($bs as $b) {
                    $b = new Crawler($b);

                    $str = trim(preg_replace('/\s+/', ' ', $b->html()));
                    $attach = htmlspecialchars_decode($str);

                    if ($bs->count() == 1)
                        $result[$attr] = $attach;
                    else
                        $result[$attr][] = $attach;
                }
            }
        }

        return $result;
    }

    /**
     * Encodes response cookies
     * @return array
     */
    protected function encodeCookies($cookies)
    {
        $result = [];

        foreach ($cookies as $cookie) {
            $result[$cookie['Name']] = $cookie['Value'];
        }

        return $result;
    }

    /**
     * Creates a http client instance.
     * @return HttpClient
     */
    protected function createClient()
    {
        return new HttpClient(array_merge([
            'verify' => false,
            'cookies' => true,
            'headers' => [
                'Cache-Control' => 'no-cache',
                'Accept' => '*/*',
                'Accept-Encoding' => 'gzip, deflate, br'
            ],
        ], $this->config));
    }

}
