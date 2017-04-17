<?php

namespace DevIT\MelodiMedia;

use SoapClient;
use DevIT\MelodiMedia\Parsers\{MelodiApiParser, XMLApiParser};
use GuzzleHttp\Psr7;

class ApiClient {

    // API GET test call example:
    // http://webservice.melodimedia.co.uk/index.cfc?method=newcontent&contenttypeid=67&siteid=631&exclusive=2&format=xml&startdate=2015-12-01
    // http://webservice.melodimedia.co.uk/index.cfc?method=contentDetailsExtended&ContentID=295264&SiteID=631&exclusive=2&format=xml

    /**
     * Melodi API endpoint.
     *
     * @var string
     */
    public static $endpoint = 'http://webservice.melodimedia.co.uk/index.cfc?wsdl';

    /**
     * Endpoint that provides downloadlinks.
     *
     * @var string
     */
    public static $downloadProvider = 'http://supersiteinsert.melodimedia.co.uk/';

    /**
     * Username to authenticate.
     *
     * @var string
     */
    protected $username;

    /**
     * Password to authenticate.
     *
     * @var string
     */
    protected $password;

    /**
     * SiteID to retrieve all content items.
     *
     * @var string
     */
    protected $siteId;

    /**
     * SoapClient instance.
     *
     * @var \SoapClient
     */
    protected $client;

    /**
     * API Parser for the melodi media api.
     * This parser converts responses to arrays.
     *
     * @var MelodiApiParser
     */

    protected $rows = 0;

    protected $columns = 0;

    protected $adult = 0;

    protected $exclusive = 0;

    protected $format = 'XML';

    /**
     * Create a new Melodi API instance.
     *
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username, string $password, string $siteId, MelodiApiParser $apiParser = null) {
        $this->client = new SoapClient(self::$endpoint, ['trace' => 1]);

        $this->siteId   = $siteId;
        $this->username = $username;
        $this->password = $password;
        $this->apiParser = $apiParser ?? new XMLApiParser();

        return $this;
    }

    public function setAdult($adult) {
        $this->adult = $adult ? '1' : '0';
    }

    public function setExclusive($exclusive) {
        if(is_bool($exclusive)) {
            $this->exclusive = $exclusive ? '1' : '0';
        } else {
            $this->exclusive = $exclusive;
        }
    }

    public function setFormat($format) {
        $this->format = strtoupper($format);
    }

    /**
     * Retrieve all content types available.
     *
     * @return array|null
     */
    public function contentTypes(): array
    {
        $response =  $this->client->ContentTypes($this->siteId);
        $contentTypes = $this->apiParser->parse($response);

        return $contentTypes['ContentType'] ?? $contentTypes;
    }

    /**
     * Retrieve categories for a certain contenttype.
     *
     * @param int  $contentTypeId
     * @param int $exclusive
     * @param bool $adult
     *
     * @return array|null
     */
    public function categoriesForContentType(int $contentTypeId, int $exclusive = 2, bool $adult = false): array
    {
        $response = $this->client->Categories(
            $this->siteId,
            $contentTypeId,
            intval($adult),
            $exclusive,
            $this->format
        );

        $categories = $this->apiParser->parse($response);

        return $categories['category'] ?? $categories;
    }

    /**
     * Fetch all content for a given category from MelodiMedia.
     *
     * @param int    $categoryId
     * @param int    $exclusive
     * @param bool   $adult
     *
     * @return mixed
     */
    public function contentForCategory(int $categoryId, int $exclusive = 2, bool $adult = false): array {
        $response = $this->client->CategoryContent(
            $this->siteId,
            $categoryId,
            $this->rows,
            $this->columns,
            $exclusive,
            intval($adult),
            $this->format
        );

        $content = $this->apiParser->parse($response);

        return $content['content'] ?? $content;
    }

    /**
     * Get details about this content item.
     *
     * @param int $contentId
     *
     * @return mixed
     */
    public function contentDetails(int $contentId) {
        $response = $this->client->ContentDetails(
            $this->siteId,
            $contentId,
            $this->format
        );

        $item = $this->apiParser->parse($response);
        if (empty($item)) {
            return $item;
        }

        return $item['content'];
    }

    /**
     * Get extended details about this content item including translations.
     *
     * @param int  $contentId
     * @param bool $includeTranslations
     *
     * @return array|null
     */
    public function contentDetailsExtended(int $contentId, bool $includeTranslations = true) {
        $response =  $this->client->ContentDetailsExtended(
            $this->siteId,
            $contentId,
            $this->format,
            intval($includeTranslations)
        );

        $item = $this->apiParser->parse($response);
        return $item;
    }

    /**
     * @param int $contentId Remote content id
     * @param     $identifier
     * @param     $country
     */
    public function getDownloadUrlForContentId(int $contentId, $identifier, $country): ?array
    {
        $client = new \GuzzleHttp\Client(['base_uri' => self::$downloadProvider]);
        $params = [
            'contentid' => $contentId,
            'mobilephone' => $identifier,
            'country' => $country,
            'username' => $this->username,
            'password' => $this->password,
            'override' => 'file',
        ];
        try {
            $response = $client->request('GET', 'cms_insert.cfm', [
                'query' => $params
            ]);
        } catch (\Exception $e) {
            $matches = [];
            if ($e->hasResponse()) {
                $body = Psr7\str($e->getResponse());
                preg_match_all("/<h1>(?<status>[0-9]{3}) (?<message>.*)<\/h1>/uism", $body, $matches);

                throw new \Exception($matches['message'][0]);
            }
            return null;
        }


        $body = $response->getBody();
        $matches = [];
        preg_match_all("/<h1>(?<status>[0-9]{3})(?<message>.*) - (?<ref>[0-9]{8})<\/h1>(?<link>.*)<\/body>/uism", $body, $matches);

        return [
            'status' => $matches['status'][0] ?? 0,
            'ref' => $matches['ref'][0] ?? '',
            'content' => trim($matches['link'][0]) ?? '',
        ];

    }
}
