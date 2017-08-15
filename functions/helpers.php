<?php

/* Helpers */
function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function get_http_response_code($url) {
    $headers = @get_headers($url);
    return substr($headers[0], 9, 3);
}

function download($url) {
    $curl = curl_init();

    curl_setopt_array($curl, array
    (
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/22.0.1207.1 Safari/537.1',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => false
    ));

    $data = curl_exec($curl);

    curl_close($curl);

    return $data;
}

function upload($url, $fields) {
    $curl = curl_init();

    curl_setopt_array($curl, array
    (
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/22.0.1207.1 Safari/537.1',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => http_build_query($fields)
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
}

function uploadThenDownload($postUrl, $getUrl, $postFields) {
    // Upload (post)
    $curl = curl_init();

    curl_setopt_array($curl, array
    (
        CURLOPT_URL => $postUrl,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/22.0.1207.1 Safari/537.1',
        CURLOPT_POST => 1,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query($postFields),
        CURLOPT_COOKIEJAR => "cookie.txt"
    ));

    $postRequest = curl_exec($curl);

    // Download (get)
    curl_setopt_array($curl, array
    (
        CURLOPT_URL => $getUrl,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/22.0.1207.1 Safari/537.1'
    ));

    $getRequest = curl_exec($curl);

    curl_close($curl);

    return $getRequest;
}

function getRedirectUrl($url) {
    $curl = curl_init();

    curl_setopt_array($curl, array
    (
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/22.0.1207.1 Safari/537.1',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false
    ));

    $response = curl_exec($curl);

    preg_match_all('/^Location:(.*)$/mi', $response, $matches);

    curl_close($curl);

    if (!empty($matches[1])) {
        return trim($matches[1][0]);
    } else {
        return $url;
    }
}

/* PRWeb */
function collectPRWebArticlesPages($sourceUrl) {
    $urls = array();

    $sourcePage = download($sourceUrl);

    // Get host w/o www.
    $sourceHost = preg_replace('#^www\.(.+\.)#i', '$1', parse_url($sourceUrl, PHP_URL_HOST));

    $document = phpQuery::newDocument($sourcePage);

    /*$pages = $document->find('ul.pagination-list li');

    foreach ($pages as $pg) {
        $page = pq($pg);
        $page_href = $page->find('a')->attr('href');
        $filters = array('index.htm', 'index.html', 'index.php');

        // Remove index.html, index.htm, index.php from the url
        $page_href = str_replace($filters, "", $page_href);

        // Get url response code to check if it is valid
        //if (get_http_response_code($targetUrl) === '404' || get_http_response_code($targetUrl) === '400') {

        // Check if href attribute is the full url or just the path
        if (strpos($page_href, $sourceHost) !== false) {
            array_push($urls, $page_href);
        } else {
            // Remove beginning slash
            substr($page_href, 0, 1) == '/' ? $page_href = substr_replace($page_href, "", 0, 1) : '';

            $targetUrl = $sourceUrl . $page_href;

            array_push($urls, $targetUrl);
        }
    }

    return $urls;*/

    $last_page = $document->find('ul.pagination-list li:last');

    $last_page_number = preg_replace("/[^0-9]/", "", $last_page->find('a')->attr('href'));

    if (!empty($last_page_number)) {
		for ($i = 1; $i <= $last_page_number; $i++) {
			$targetUrl = $i === 1 ? $sourceUrl : $sourceUrl . $i . ".htm";

			array_push($urls, $targetUrl);
		}
	} else {
		array_push($urls, $sourceUrl);
	}

    return $urls;
}

function getPRWebContactUrl($url, $sleep) {
    $articlePage = download($url);

    $documentArticle = phpQuery::newDocument($articlePage);

    $contactButtonHref = $documentArticle->find('a.box-contact-btn')->attr('href');

    sleep($sleep);
    $contactButtonRedirectedUrl = getRedirectUrl($contactButtonHref);

    return $contactButtonRedirectedUrl;
}

/* PROZ */
function collectPROZProfilesPages($sourceUrl) {
    $urls = array();

    $sourcePage = download($sourceUrl);

    $document = phpQuery::newDocument($sourcePage);

    $pager_string = $document->find('#pager_string');

    $total_profiles = $pager_string->siblings('b:first')->text();

    $profiles_page_query = "?p=1&submit=1&nshow=20&start=";

    for ($i = 0; $i <= $total_profiles; $i += 20) {
        array_push($urls, $sourceUrl . $profiles_page_query . $i);
    }

    return $urls;
}

function getPROZProfileUrl($url,  $sleep) {
    $profilePage = download($url);

    $profileDocument = phpQuery::newDocument($profilePage);

    $profileURL = $profileDocument->find('.contact_column > a')->attr('href');

    return $profileURL;
}

/* GetApp */
function collectGetAppCategoriesData($sourceUrl) {
    $urls = array();

    $sourcePage = download($sourceUrl);

    $document = phpQuery::newDocument($sourcePage);

    $categories = $document->find('.container .masonry > .block > ul > li');

    foreach ($categories as $category_item) {
        $category_item_query = pq($category_item);

        $category_href = $category_item_query->find('a:first')->attr('href');

        $category_name = $category_item_query->find('a:first')->text();

        array_push($urls, array(
            'category_href' => $category_href,
            'category_name' => $category_name
        ));
    }

    return $urls;
}

function collectGetAppArticlesPages($sourceUrl, $sleep, $pageNumber = 10, $maxFoundPageNumber = NULL) {
    $urls = array();

    // Find out how many pages do we have
    // by incrementing/decrementing page number
    // and checking whether there are or not articles on that page
    $checkUrl = $sourceUrl . "?page={$pageNumber}";

    sleep($sleep);

    $checkPage = download($checkUrl);

    $checkDocument = phpQuery::newDocument($checkPage);

    $article = $checkDocument->find('.listing_entry:first')->length;

    if ($article) {
        return collectGetAppArticlesPages($sourceUrl, $sleep, ++$pageNumber, --$pageNumber);
    } else {
        if (!empty($maxFoundPageNumber)) {
            for ($i = 1; $i <= $maxFoundPageNumber; $i++) {
                array_push($urls, $sourceUrl . "?page={$i}");
            }

            return $urls;
        } else {
            return collectGetAppArticlesPages($sourceUrl, $sleep, --$pageNumber, NULL);
        }
    }
}

function getGetAppRedirectContactUrl($url, $sleep) {
    // Delay before http query
    sleep($sleep);

    $context = stream_context_create(
        array(
            'http' => array(
                'follow_location' => false // don't follow redirects
            )
        )
    );

    $article_page = file_get_contents($url, false, $context);

    $article_page_document = phpQuery::newDocument($article_page);

    $redirect_url = get_string_between($article_page_document->text(), 'location.replace("', '")');

    return $redirect_url;
}

function getGetAppAlternativeContactData($url, $sleep) {
    sleep($sleep);

    $page = download($url);

    $document = phpQuery::newDocument($page);

    $company_alternative_name_container = $document->find('span[itemprop="author"]:first');

    $company_alternative_name = $company_alternative_name_container->find('span[itemprop="name"]')->text();

    $company_alternative_url = $company_alternative_name_container->parent()->siblings('.text-muted')->text();

    $data = array(
        'name' => $company_alternative_name,
        'url' => $company_alternative_url
    );

    return $data;
}

/* Auriga */
function collectAurigaProfilesPages($sourceUrl) {
    $urls = array();

    for ($i = 1; $i <= 17185; $i++) {
        array_push($urls, "{$sourceUrl}?NumPage={$i}&Lista=");
    }

    return $urls;
}

function downloadAurigaProfilesPage($url, $sleep) {
    sleep($sleep);

    $sourcePage = uploadThenDownload("http://auriga.ice.it/opportunitaaffari/offertaitaliana/web_new/RispostaRicercaInferiore.asp", $url, array(
        'HInAttivita' => '',
        'hiddenNomiCheck' => '',
        'NomeRegione' => '',
        'pagina_interessi' => '',
        'Paese' => '',
        'DescAttivita' => 'clients',
        'TipoRicerca' => 2,
        'DescAttivita2' => '',
        'Caricato2' => 1,
        'BB' => 0
    ));

    $page_document = phpQuery::newDocument($sourcePage);

    $profiles = $page_document->find('form#Form1 > table:even');

    return $profiles;
}

/* AppleGate */
function collectAppleGateArticlesPages($sourceUrl, $sleep) {
    // Full articles urls
    $urls = array();

    // Being not obvious
    sleep($sleep);

    // Download the page
    $categoryPage = download($sourceUrl);

    // Inject phpQuery into downloaded page
    $document = phpQuery::newDocument($categoryPage);

    // Fetch the highest pagination value
    $totalPaginationElements = $document->find('ol.page-selector > li')->count();
    $highestPaginationValue = $document->find('ol.page-selector > li')->eq($totalPaginationElements - 2)->text();

    for ($i = 1; $i <= trim($highestPaginationValue); $i++) {
        array_push($urls, $sourceUrl . "?page={$i}&size=1000");
    }

    return $urls;
}
