<?php

// Require scripts
require '../vendor/autoload.php';
require 'phpQuery.php';
require 'helpers.php';
require 'db.php';

// Use libraries
use Carbon\Carbon;

// Modify php configurations
set_time_limit(0); // Unlimited

// Initialize timer
$time = -microtime(true);

// Fetch POST URL
$url_array = $_POST['url'];
$url_key = current(array_keys($url_array));
$url_value = trim(current($url_array));

// Check for emptiness
if (!isset($url_array) || empty($url_value)) die('URL is empty');

// Process
switch ($url_key) {
    case 'prweb': {
        processPRWeb($time, $url_value);
        break;
    }

    case 'proz': {
        processPROZ($time, $url_value);
        break;
    }

    case 'getapp': {
        processGetApp($time, $url_value);
        break;
    }

    case 'auriga': {
        processAuriga($time, $url_value);
        break;
    }

    case 'applegate': {
        processAppleGate($time, $url_value);
        break;
    }
}

// Process functions
function processPRWeb($time, $url_value) {
    global $pdo;

    // Progress
    echo '<span id="progress-category" style="font-weight: bold; font-size: 22px;"></span>';
    echo '<span id="progress-page" style="font-weight: bold; font-size: 22px;"></span>';
    echo '<span id="progress-article" style="font-weight: bold; font-size: 22px;"></span>';

    $summary = array(
        'categories' => 0,
        'pages' => 0,
        'articles' => 0,
        'contact_links' => 0,
        'unique_links' => 0,
        'time' => 0
    );

    $files = array();

    $k = 0;
    $sleep = 1;

    $postUrls = explode(PHP_EOL, $url_value);

    foreach ($postUrls as $postUrl) {
        if (empty($postUrl)) die('URL is empty');

        // Add http://www. to the url - http://www.domain.com/...
        $sourceUrl = parse_url($postUrl);
        $sourceUrl = preg_replace('#^www\.(.+\.)#i', '$1', $sourceUrl['host'] . $sourceUrl['path']);
        $sourceUrl = 'http://www.' . $sourceUrl;

        // Remove index.html, index.htm, index.php from the url
        $filters = array('index.htm', 'index.html', 'index.php');
        $sourceUrl = str_replace($filters, "", $sourceUrl);

        // Add ending slash
        substr($sourceUrl, -1) != '/' ? $sourceUrl .= '/' : '';

        // Get homepage
        $sourceHomepage = parse_url($sourceUrl);
        $sourceHomepage = $sourceHost = preg_replace('#^www\.(.+\.)#i', '$1', $sourceHomepage['host']);
        $sourceHomepage = 'http://www.' . $sourceHomepage . '/';

        $articlesUrls = collectPRWebArticlesPages($sourceUrl);

        $filename = preg_replace('#^www\.(.+\.)#i', '$1', parse_url($sourceUrl, PHP_URL_HOST)) . '-' . Carbon::today()->toDateString() . '-' . rand(0, 999);
        $file = fopen(dirname(__DIR__) . '/files/' . $filename . '.txt', 'w') or die('Unable to open file!');

        $i = 0;

        $counter_categories = count($postUrls);
        $summary['categories']++;

        // Progress page
        $k++;
        echo "<script id='progress-category-script'>
                document.getElementById('progress-category').innerHTML = 'Category ' + $k + ' of ' + $counter_categories + ' categories... <br />';
                document.body.removeChild(document.getElementById('progress-category-script'));
              </script>";
        flush();

        foreach ($articlesUrls as $artUrl) {
            $j = 0;

            $counter_pages = count($articlesUrls);
            $summary['pages']++;

            // Progress page
            $i++;
            echo "<script id='progress-page-script'>
                    document.getElementById('progress-page').innerHTML = 'Page ' + $i + ' of ' + $counter_pages + ' pages... <br />';
                    document.body.removeChild(document.getElementById('progress-page-script'));
                  </script>";
            flush();

            sleep($sleep);
            $sourcePage = download($artUrl);

            $document = phpQuery::newDocument($sourcePage);

            $articles = $document->find('div.article-box-cont');

            foreach ($articles as $art) {
                $counter_arts = count($articles);
                $summary['articles']++;

                // Progress article
                $j++;
                echo "<script id='progress-article-script'>
                        document.getElementById('progress-article').innerHTML = 'Article ' + $j + ' of page ' + $i + ' of ' + $counter_arts + ' articles... <br />';
                        document.body.removeChild(document.getElementById('progress-article-script'));
                      </script>";
                flush();

                $article = pq($art);

                //$article_href = $article->find('article.article-box > a.qa-link-to-release')->attr('href');
                $article_href = $article->find('article.article-box > a:first')->attr('href');

                // Get url response code to check if it is valid
                //if (get_http_response_code($targetUrl) === '404' || get_http_response_code($targetUrl) === '400') {

                // Check if href attribute is the full url or just the path
                if (strpos($article_href, $sourceHost) !== false) {
                    $completeUrl = $article_href;

                    sleep($sleep);
                    $url = getPRWebContactUrl($article_href, $sleep);
                } else {
                    // Remove beginning slash
                    substr($article_href, 0, 1) == '/' ? $article_href = substr_replace($article_href, "", 0, 1) : '';

                    $targetUrl = $completeUrl = $sourceHomepage . $article_href;

                    sleep($sleep);
                    $url = getPRWebContactUrl($targetUrl, $sleep);
                }

                !empty($url) ? $summary['contact_links']++ : '';

                // Check for urls in the DB
                $redirection_url_domain = preg_replace('#^www\.(.+\.)#i', '$1', parse_url($url, PHP_URL_HOST));

                //$query = $pdo->prepare('SELECT * FROM `links` WHERE `redirection_domain` = :url');
                //$query->execute(array(':url' => $redirection_url_domain));
                //$count = $query->rowCount();

                //if (empty($count) && !empty($url)) {
                if (!empty($url)) {
                    // Write to DB
                    $query = $pdo->prepare('INSERT INTO `links` (`parser_type`, `prweb_redirection_link`) VALUES (?, ?)');
                    $query->execute(array(
                        'prweb',
                        $url
                    ));

                    $summary['unique_links']++;

                    /*$text = "Article URL: " . $completeUrl . "\n";
                    $text .= "Contact URL (after redirect): " . $url . "\n\n";*/

                    $text = "http://www." . $redirection_url_domain . "\n";

                    fwrite($file, $text);
                }
            }
        }

        fclose($file);

        $file_url = (!empty($_SERVER['HTTPS'])) ? "https://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] : "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        $file_url = dirname(dirname($file_url)) . "/files/" . $filename . ".txt";

        array_push($files, array(
            'source' => $postUrl,
            'file' => $file_url
        ));
    }

    $time += microtime(true);
    $summary['time'] = $time;

    echo "<br /> <span style='font-weight: bold; font-size: 22px;'>Summary:</span>";
    echo "<br /> <b>Categories (source URLs): </b>" . $summary['categories'];
    echo "<br /> <b>Pages: </b>" . $summary['pages'];
    echo "<br /> <b>Articles: </b>" . $summary['articles'];
    echo "<br /> <b>Contact links (after redirect): </b>" . $summary['contact_links'];
    echo "<br /> <b>Unique contact links (after redirect): </b>" . $summary['unique_links'];
    echo "<br /> <b>Runtime total: </b>" . $summary['time'] . " seconds.";
    echo "<br /> <b>Download .txt files: </b>";

    foreach ($files as $f) {
        echo "<br /> - <a href=" . $f['file'] . " target='_blank'>" . $f['source'] . "</a>";
    }
}

function processPROZ($time, $url_value) {
    global $pdo;

    // Progress
    echo '<span id="progress-page" style="font-weight: bold; font-size: 22px;"></span>';
    echo '<span id="progress-profile" style="font-weight: bold; font-size: 22px;"></span>';

    $summary = array(
        'pages' => 0,
        'profiles' => 0,
        'contact_links' => 0,
        'unique_links' => 0,
        'time' => 0
    );

    $sleep = 1;

    $postUrl = $url_value;

    if (empty($postUrl)) die('URL is empty');

    // Add http://www. to the url - http://www.domain.com/...
    $sourceUrl = parse_url($postUrl);
    $sourceUrl = preg_replace('#^www\.(.+\.)#i', '$1', $sourceUrl['host'] . $sourceUrl['path']);
    $sourceUrl = 'http://www.' . $sourceUrl;

    // Remove index.html, index.htm, index.php from the url
    $filters = array('index.htm', 'index.html', 'index.php');
    $sourceUrl = str_replace($filters, "", $sourceUrl);

    // Remove ending slash
    substr($sourceUrl, -1) == '/' ? $sourceUrl = rtrim($sourceUrl, "/") : '';

    // Get homepage
    $sourceHomepage = parse_url($sourceUrl);
    $sourceHomepage = $sourceHost = preg_replace('#^www\.(.+\.)#i', '$1', $sourceHomepage['host']);
    $sourceHomepage = 'http://www.' . $sourceHomepage . '/';

    $profilesUrls = collectPROZProfilesPages($sourceUrl);

    $i = 0;

    foreach ($profilesUrls as $pageNumber => $profileUrl) {
        $j = 0;

        $counter_pages = count($profilesUrls);
        $summary['pages']++;

        // Progress page
        $i++;
        echo "<script id='progress-page-script'>
                document.getElementById('progress-page').innerHTML = 'Page ' + $i + ' of ' + $counter_pages + ' pages... <br />';
                document.body.removeChild(document.getElementById('progress-page-script'));
              </script>";
        flush();

        // Temporary memory solutions (split by pages)
        $pageNumber++;

        if ($pageNumber < 2500) {
            continue;
        }/* elseif ($pageNumber >= 2500) {
            break;
        }*/

        sleep($sleep);
        $sourcePage = download($profileUrl);

        $page_document = phpQuery::newDocument($sourcePage);

        $profiles = $page_document->find('[name="submit_cd_form"]')->siblings('table')->eq(1)->find('tbody > tr');

        foreach ($profiles as $profile) {
            $counter_profiles = count($profiles);
            $summary['profiles']++;

            // Progress profile
            $j++;
            echo "<script id='progress-profile-script'>
                    document.getElementById('progress-profile').innerHTML = 'Profile ' + $j + ' of page ' + $i + ' of ' + $counter_profiles + ' profiles... <br />';
                    document.body.removeChild(document.getElementById('progress-profile-script'));
                  </script>";
            flush();

            $profile_query = pq($profile);

            $profile_href = $profile_query->find('h2 > a')->attr('href');
            $profile_name = $profile_query->find('h2 > a')->text();

            // Check if href attribute is the full url or just the path
            if (strpos($profile_href, $sourceHost) !== false) {
                sleep($sleep);
                $url = getPROZProfileUrl($profile_href, $sleep);
            } else {
                // Remove beginning slash
                substr($profile_href, 0, 1) == '/' ? $profile_href = substr_replace($profile_href, "", 0, 1) : '';

                $targetUrl = $completeUrl = $sourceHomepage . $profile_href;

                sleep($sleep);
                $url = getPROZProfileUrl($targetUrl, $sleep);
            }

            if (!empty($url)) {
                $summary['contact_links']++;
                $summary['unique_links']++;
            }

            if (!empty($profile_name) || !empty($url)) {
                // Write to DB
                $query = $pdo->prepare('INSERT INTO `links` (`parser_type`, `proz_company`, `proz_link`) VALUES (?, ?, ?)');
                $query->execute(array(
                    'proz',
                    $profile_name ?: '',
                    $url ?: ''
                ));
            }
        }

        //if ($summary['pages'] == 2) break;
    }

    $time += microtime(true);
    $summary['time'] = $time;

    echo "<br /> <span style='font-weight: bold; font-size: 22px;'>Summary:</span>";
    echo "<br /> <b>Pages: </b>" . $summary['pages'];
    echo "<br /> <b>Profiles: </b>" . $summary['profiles'];
    echo "<br /> <b>Contact links: </b>" . $summary['contact_links'];
    echo "<br /> <b>Unique contact links: </b>" . $summary['unique_links'];
    echo "<br /> <b>Runtime total: </b>" . $summary['time'] . " seconds.";
}

function processGetApp($time, $url_value) {
    global $pdo;

    // Progress
    echo '<span id="progress-category" style="font-weight: bold; font-size: 22px;"></span>';
    echo '<span id="progress-page" style="font-weight: bold; font-size: 22px;"></span>';
    echo '<span id="progress-article" style="font-weight: bold; font-size: 22px;"></span>';

    // Summary
    $summary = array(
        'categories' => 0,
        'pages' => 0,
        'articles' => 0,
        'contact_links' => 0,
        'unique_links' => 0,
        'time' => 0
    );

    // Categories increment
    $k = 0;

    // Sleep timer before each request
    $sleep = 1;

    // Start URL not being empty
    $postUrl = $url_value;

    if (empty($postUrl)) die('URL is empty');

    // Add http://www. to the url - http://www.domain.com/...
    $sourceUrl = parse_url($postUrl);
    $sourceUrl = preg_replace('#^www\.(.+\.)#i', '$1', $sourceUrl['host'] . $sourceUrl['path']);
    $sourceUrl = 'http://www.' . $sourceUrl;

    // Remove index.html, index.htm, index.php from the url
    $filters = array('index.htm', 'index.html', 'index.php');
    $sourceUrl = str_replace($filters, "", $sourceUrl);

    // Remove ending slash
    substr($sourceUrl, -1) == '/' ? $sourceUrl = rtrim($sourceUrl, "/") : '';

    // Get homepage
    $sourceHomepage = parse_url($sourceUrl);
    $sourceHomepage = $sourceHost = preg_replace('#^www\.(.+\.)#i', '$1', $sourceHomepage['host']);
    $sourceHomepage = 'http://www.' . $sourceHomepage . '/';

    // Fetch each category name and link to it
    $categoriesData = collectGetAppCategoriesData($sourceUrl);

    // Parse each category
    foreach ($categoriesData as $categoryData) {
        $counter_categories = count($categoriesData);
        $summary['categories']++;

        // Articles page increment
        $i = 0;

        // Progress category
        $k++;
        echo "<script id='progress-category-script'>
                document.getElementById('progress-category').innerHTML = 'Category ' + $k + ' of ' + $counter_categories + ' categories... <br />';
                document.body.removeChild(document.getElementById('progress-category-script'));
              </script>";
        flush();

        $categoryHref = $categoryData['category_href'];

        $categoryName = $categoryData['category_name'];

        if (strpos($categoryHref, $sourceHost) === false) {
            // Remove beginning slash
            substr($categoryHref, 0, 1) == '/' ? $categoryHref = substr_replace($categoryHref, "", 0, 1) : '';

            $categoryHref = $sourceHomepage . $categoryHref;
        }

        $articlesPagesUrls = collectGetAppArticlesPages($categoryHref, $sleep);

        foreach ($articlesPagesUrls as $articlePageUrl) {
            $counter_pages = count($articlesPagesUrls);
            $summary['pages']++;

            // Articles increment
            $j = 0;

            // Progress page
            $i++;
            echo "<script id='progress-page-script'>
                    document.getElementById('progress-page').innerHTML = 'Page ' + $i + ' of ' + $counter_pages + ' pages... <br />';
                    document.body.removeChild(document.getElementById('progress-page-script'));
                  </script>";
            flush();

            sleep($sleep);

            $sourcePage = download($articlePageUrl);

            $page_document = phpQuery::newDocument($sourcePage);

            $articles = $page_document->find('li.listing_entry');

            foreach ($articles as $article) {
                $counter_articles = count($articles);
                $summary['articles']++;

                // Progress article
                $j++;
                echo "<script id='progress-article-script'>
                        document.getElementById('progress-article').innerHTML = 'Article ' + $j + ' of page ' + $i + ' of ' + $counter_articles + ' articles... <br />';
                        document.body.removeChild(document.getElementById('progress-article-script'));
                      </script>";
                flush();

                $article_query = pq($article);

                $company_name = $article_title_name = $article_query->find('h2 > a:first')->text();

                $article_title_href = $article_query->find('h2 > a:first')->attr('href');

                $article_short_description = $article_query->find('.short-overview');

                $article_short_description_href = $article_short_description->find('a:first')->attr('href');

                if (strpos($article_title_href, $sourceHost) === false) {
                    // Remove beginning slash
                    substr($article_title_href, 0, 1) == '/' ? $article_title_href = substr_replace($article_title_href, "", 0, 1) : '';

                    $article_title_href = $sourceHomepage . $article_title_href;
                }

                if (strpos($article_short_description_href, $sourceHost) === false) {
                    // Remove beginning slash
                    substr($article_short_description_href, 0, 1) == '/' ? $article_short_description_href = substr_replace($article_short_description_href, "", 0, 1) : '';

                    $article_short_description_href = $sourceHomepage . $article_short_description_href;
                }

                $company_redirect_url = "";

                // Check if the URL contains /x/ which hopefully means it's a redirect link
                if (strpos($article_title_href, "/x/") !== false) {
                    $company_redirect_url = getGetAppRedirectContactUrl($article_title_href, $sleep);
                }

                // Fetch alternative company name and href
                $company_alternative_data = getGetAppAlternativeContactData($article_short_description_href, $sleep);

                if (!empty($company_redirect_url) || !empty($company_alternative_data['url'])) {
                    $summary['contact_links']++;
                    $summary['unique_links']++;
                }

                if (!empty($categoryName) ||
                    !empty($company_name) ||
                    !empty($company_redirect_url) ||
                    !empty($company_alternative_data['name']) ||
                    !empty($company_alternative_data['url'])
                ) {
                    // Write to DB
                    $query = $pdo->prepare('INSERT INTO `links` (`parser_type`, `getapp_category`, `getapp_company`, `getapp_redirection_link`, `getapp_software_by`, `getapp_link`) VALUES (?, ?, ?, ?, ?, ?)');
                    $query->execute(array(
                        'getapp',
                        $categoryName ?: '',
                        $company_name ?: '',
                        $company_redirect_url ?: '',
                        !empty($company_alternative_data['name']) ? $company_alternative_data['name'] : '',
                        !empty($company_alternative_data['url']) ? $company_alternative_data['url'] : ''
                    ));
                }
            }
        }
    }

    $time += microtime(true);
    $summary['time'] = $time;

    echo "<br /> <span style='font-weight: bold; font-size: 22px;'>Summary:</span>";
    echo "<br /> <b>Categories: </b>" . $summary['categories'];
    echo "<br /> <b>Pages: </b>" . $summary['pages'];
    echo "<br /> <b>Articles: </b>" . $summary['articles'];
    echo "<br /> <b>Contact links (after redirect): </b>" . $summary['contact_links'];
    echo "<br /> <b>Unique contact links (after redirect): </b>" . $summary['unique_links'];
    echo "<br /> <b>Runtime total: </b>" . $summary['time'] . " seconds.";
}

function processAuriga($time, $url_value) {
    global $pdo;

    // Progress
    echo '<span id="progress-page" style="font-weight: bold; font-size: 22px;"></span>';
    echo '<span id="progress-profile" style="font-weight: bold; font-size: 22px;"></span>';

    $summary = array(
        'pages' => 0,
        'profiles' => 0,
        'emails' => 0,
        'website_links' => 0,
        'time' => 0
    );

    $sleep = 1;

    $sourceUrl = $postUrl = $url_value;

    if (empty($postUrl)) die('URL is empty');

    $profilesPagesUrls = collectAurigaProfilesPages($sourceUrl);

    $i = 0;

    foreach ($profilesPagesUrls as $pageNumber => $profilePageUrl) {
        $j = 0;

        $counter_pages = count($profilesPagesUrls);
        $summary['pages']++;

        // Progress page
        $i++;
        echo "<script id='progress-page-script'>
                document.getElementById('progress-page').innerHTML = 'Page ' + $i + ' of ' + $counter_pages + ' pages... <br />';
                document.body.removeChild(document.getElementById('progress-page-script'));
              </script>";
        flush();

        // Temporary memory solutions (split by pages)
        $pageNumber++;

        if ($pageNumber < 1) {
            continue;
        } elseif ($pageNumber >= 2000) {
            break;
        }

        $profiles = downloadAurigaProfilesPage($profilePageUrl, $sleep);

        foreach ($profiles as $profile) {
            $counter_profiles = count($profiles);
            $summary['profiles']++;

            // Progress profile
            $j++;
            echo "<script id='progress-profile-script'>
                    document.getElementById('progress-profile').innerHTML = 'Profile ' + $j + ' of page ' + $i + ' of ' + $counter_profiles + ' profiles... <br />';
                    document.body.removeChild(document.getElementById('progress-profile-script'));
                  </script>";
            flush();

            $profile_query = pq($profile);

            $profile_email = $profile_query->find('a[onclick^=mailto]:first')->text();
            $profile_website_link = $profile_query->find('a[onclick^=siteweb]:first')->text();

            if (!empty($profile_email)) {
                $summary['emails']++;
            }

            if (!empty($profile_website_link)) {
                $summary['website_links']++;
            }

            if (!empty($profile_email) || !empty($profile_website_link)) {
                // Write to DB
                $query = $pdo->prepare('INSERT INTO `links` (`parser_type`, `auriga_email`, `auriga_website_link`) VALUES (?, ?, ?)');
                $query->execute(array(
                    'auriga',
                    $profile_email ?: '',
                    $profile_website_link ?: ''
                ));
            }
        }

        //if ($summary['pages'] == 2) break;
    }

    $time += microtime(true);
    $summary['time'] = $time;

    echo "<br /> <span style='font-weight: bold; font-size: 22px;'>Summary:</span>";
    echo "<br /> <b>Pages: </b>" . $summary['pages'];
    echo "<br /> <b>Profiles: </b>" . $summary['profiles'];
    echo "<br /> <b>Emails: </b>" . $summary['emails'];
    echo "<br /> <b>Website links: </b>" . $summary['website_links'];
    echo "<br /> <b>Runtime total: </b>" . $summary['time'] . " seconds.";
}

function processAppleGate($time, $url_value) {
    global $pdo;

    // Show progress?
    $showProgress = false;

    // Progress
    if ($showProgress) {
        // Progress template
        //echo '<span id="progress-category" style="font-weight: bold; font-size: 22px;"></span>';
        echo '<span id="progress-page" style="font-weight: bold; font-size: 22px;"></span>';
        echo '<span id="progress-article" style="font-weight: bold; font-size: 22px;"></span>';

        // Categories increment
        $k = 0;
    }

    // Sleep timer before each request
    $sleep = 1;

    // Start URL not being empty
    $postUrl = $url_value;

    if (empty($postUrl)) die('URL is empty');

    // Add https://www. to the url -> https://www.domain.com/...
    $sourceUrl = parse_url($postUrl);
    $sourceUrl = preg_replace('#^www\.(.+\.)#i', '$1', $sourceUrl['host'] . $sourceUrl['path']);
    $sourceUrl = 'https://www.' . $sourceUrl;

    // Remove index.html, index.htm, index.php from the url
    $filters = array('index.htm', 'index.html', 'index.php');
    $sourceUrl = str_replace($filters, "", $sourceUrl);

    // Add ending slash
    substr($sourceUrl, -1) != '/' ? $sourceUrl .= '/' : '';

    // Get homepage
    $sourceHomepage = parse_url($sourceUrl);
    $sourceHomepage = $sourceHost = preg_replace('#^www\.(.+\.)#i', '$1', $sourceHomepage['host']);
    $sourceHomepage = 'https://www.' . $sourceHomepage . '/';

    /*// Set the alphabet letters which represent the articles categories
    $alphas = range('a', 'z');
    array_push($alphas, "%23");

    // Categories count
    $counter_categories = count($alphas);

    // Parse each category
    foreach ($alphas as $category) {
        if ($showProgress) {
            // Articles page increment
            $i = 0;

            // Progress category
            isset($k) ? $k++ : $k = 0;

            echo "<script id='progress-category-script'>
                    document.getElementById('progress-category').innerHTML = 'Category ' + $k + ' of ' + $counter_categories + ' categories... <br />';
                    document.body.removeChild(document.getElementById('progress-category-script'));
                  </script>";

            flush();
        }
    }*/

    $category = $_POST['category'];

    $categoryHref = $sourceUrl . $category;

    $articlesPagesUrls = collectAppleGateArticlesPages($categoryHref, $sleep);

    // Pages with articles count
    $counter_pages = count($articlesPagesUrls);

    // Articles page increment
    $i = 0;

    foreach ($articlesPagesUrls as $articlesPageUrl) {
        if ($showProgress) {
            // Articles increment
            $j = 0;

            // Progress page
            isset($i) ? $i++ : $i = 0;

            echo "<script id='progress-page-script'>
                    document.getElementById('progress-page').innerHTML = 'Page ' + $i + ' of ' + $counter_pages + ' pages... <br />';
                    document.body.removeChild(document.getElementById('progress-page-script'));
                  </script>";

            flush();
        }

        sleep($sleep);

        $sourcePage = download($articlesPageUrl);

        $page_document = phpQuery::newDocument($sourcePage);

        $articles = $page_document->find('ul.supplier-list > li.supplier');

        // Articles per page count
        $counter_articles = count($articles);

        foreach ($articles as $article) {
            if ($showProgress) {
                // Progress article
                isset($j) ? $j++ : $j = 0;

                echo "<script id='progress-article-script'>
                            document.getElementById('progress-article').innerHTML = 'Article ' + $j + ' of page ' + $i + ' of ' + $counter_articles + ' articles... <br />';
                            document.body.removeChild(document.getElementById('progress-article-script'));
                          </script>";

                flush();
            }

            // Fetch article data
            $article_query = pq($article);

            // Article title
            $article_title = trim($article_query->find('a:first')->text());

            // Article internal link
            $article_internal_link = $article_query->find('a:first')->attr('href');

            if (strpos($article_internal_link, $sourceHost) === false) {
                // Remove beginning slash
                substr($article_internal_link, 0, 1) == '/' ? $article_internal_link = substr_replace($article_internal_link, "", 0, 1) : '';

                $article_internal_link = $sourceHomepage . $article_internal_link;
            }

            $article_internal_link .= "/contact-details";

            // Fetch article external link
            //sleep($sleep);

            $articlePage = download($article_internal_link);

            $article_page_document = phpQuery::newDocument($articlePage);

            $article_external_link = $article_page_document->find('.phone-and-website > span:eq(1) > a.ga-track')->text();

            if (!empty($category) || !empty($article_title) || !empty($article_external_link)) {
                // Write to DB
                $query = $pdo->prepare('INSERT INTO `links` (`parser_type`, `applegate_category`, `applegate_company`, `applegate_website_link`) VALUES (?, ?, ?, ?)');
                $query->execute(array(
                    'applegate', $category ?: '', $article_title ?: '', $article_external_link ?: '',
                ));
            }

            // Clean up
            unset($article_query, $article_title, $article_internal_link, $articlePage, $article_page_document, $article_external_link);
        }
    }

    $time += microtime(true);

    echo "<br /> <b>Runtime total: </b>{$time} seconds.";
}
