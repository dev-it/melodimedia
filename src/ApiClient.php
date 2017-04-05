<?php

namespace DevIT\MelodiMedia;

use SoapClient;
use DevIT\MelodiMedia\Parsers\{MelodiApiParser, XMLApiParser};

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
    public function contentTypes(): ?array
    {
        $response =  $this->client->ContentTypes($this->siteId);
        return $this->apiParser->parse($response)['ContentType'];
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
    public function categoriesForContentType(int $contentTypeId, int $exclusive = 2, bool $adult = false): ?array
    {
        $categories = $this->client->Categories(
            $this->siteId,
            $contentTypeId,
            intval($adult),
            $exclusive,
            $this->format
        );

        return $this->apiParser->parse($categories)['category'];
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
    public function contentForCategory(int $categoryId, int $exclusive = 2, bool $adult = false) {
        $content = $this->client->CategoryContent(
            $this->siteId,
            $categoryId,
            $this->rows,
            $this->columns,
            $exclusive,
            intval($adult),
            $this->format
        );

        return $this->apiParser->parse($content)['content'];
    }

    /**
     * Get details about this content item.
     *
     * @param int $contentId
     *
     * @return mixed
     */
    public function contentDetails(int $contentId) {
        $content = $this->client->ContentDetails(
            $this->siteId,
            $contentId,
            $this->format
        );

        return $this->apiParser->parse($content)['content'];
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
        $extendedContent =  $this->client->ContentDetailsExtended(
            $this->siteId,
            $contentId,
            $this->format,
            intval($includeTranslations)
        );

        return $this->apiParser->parse($extendedContent);
    }
}
