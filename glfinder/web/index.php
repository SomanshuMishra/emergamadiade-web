<?php

function callAPI($method, $url, $data)
{
    $curl = curl_init();
    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }
    // OPTIONS:
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    // EXECUTE:
    $result = curl_exec($curl);
    if (!$result) {
        die("Connection Failure");
    }
    curl_close($curl);
    return $result;
}

function isValidTimeStamp($timestamp)
{
    return ((string)(int)$timestamp === $timestamp)
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
}

function get_content($URL)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function results($timestamp, $subreddit, $size)
{
    /*

    http://glfinder-api:8080/feeds/all/<subreddit>/<rlimit>
    http://glfinder-api:8080/feeds/all/<subreddit>/<timestamp>/<rlimit>
    http://glfinder-api:8080/feeds/filter/<subreddit>/<word>/

    */
    $data = get_content("http://glfinder-api:8080/feeds/all/$subreddit/$timestamp/$size");
    return json_decode($data, true);
}

$subreddits_array = ["fashionreps", "repsneakers", "designerreps", "flexicas", "reptime", "repladies"];
$entries_array = ["150", "300", "450"];

if (isset($_GET['search'])) {

    $search_query = filter_input(INPUT_POST, 'search_query', FILTER_SANITIZE_STRING);
    $subreddit = filter_input(INPUT_POST, 'subreddit', FILTER_SANITIZE_STRING);

    if (in_array($subreddit, $subreddits_array)) {
        $data = callAPI('POST', "http://glfinder-api:8080/feeds/filter/$subreddit", json_encode(array("keyword" => $search_query), JSON_UNESCAPED_SLASHES));
        die($data);
    } else {
        die(json_encode(array("status" => "failed")));
    }

}

if (isset($_GET['subreddit']) && in_array($_GET['subreddit'], $subreddits_array)) {
    $subreddit = $_GET['subreddit'];
    $next_item = "?subreddit=$subreddit&next=";
} else {
    $subreddit = 'fashionreps';
    $next_item = '?next=';
}

if (isset($_GET['next']) && isValidTimeStamp($_GET['next']) == true) {
    $timestamp = $_GET['next'];
} else {
    $timestamp = time();
}

if (isset($_GET['size']) && in_array($_GET['size'], $entries_array)) {
    $size = $_GET['size'];
    $size_get = "&size=$size";
} else {
    $size = 150;
}

if (isset($_GET['w2c_only']) && $_GET['w2c_only'] == 'yes') {
    $w2c_only = 'checked';
    $w2c_uri = '&w2c_only=yes';
}


?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta subreddit="<?php echo $subreddit; ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>GLFinder - Replica Quality Control</title>
    <meta name="title" content="GLFinder - Quality Control your Replicas!">
    <meta name="description" content="Archive of replicas that have gone through the quality control and successfully passed through.">
    <link rel="icon" type="image/png" href="assets/favicon.png"/>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/8a64eb2445.js" crossorigin="anonymous"></script>
    <script src="assets/js/lazyload.min.js"></script>

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-149670194-2"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        gtag('config', 'UA-149670194-2');
    </script>

    <style>
        #return-to-top {
            z-index: 1000;
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgb(0, 0, 0);
            background: rgba(0, 0, 0, 0.7);
            width: 50px;
            height: 50px;
            display: block;
            text-decoration: none;
            -webkit-border-radius: 35px;
            -moz-border-radius: 35px;
            border-radius: 35px;
            display: none;
            -webkit-transition: all 0.3s linear;
            -moz-transition: all 0.3s ease;
            -ms-transition: all 0.3s ease;
            -o-transition: all 0.3s ease;
            transition: all 0.3s ease;
        }

        #return-to-top i {
            color: #fff;
            margin: 0;
            position: relative;
            left: 16px;
            top: 13px;
            font-size: 19px;
            -webkit-transition: all 0.3s ease;
            -moz-transition: all 0.3s ease;
            -ms-transition: all 0.3s ease;
            -o-transition: all 0.3s ease;
            transition: all 0.3s ease;
        }

        #return-to-top:hover {
            background: rgba(0, 0, 0, 0.9);
        }

        #return-to-top:hover i {
            color: #fff;
            top: 5px;
        }

        .bd-placeholder-img {
            font-size: 1.125rem;
            text-anchor: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        p.card-text {
            white-space: nowrap;
            width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        img {
            opacity: 0;
        }

        img:not(.initial) {
            transition: opacity 1s;
        }

        img.initial,
        img.loaded,
        img.error {
            opacity: 1;
        }

        img:not([src]) {
            visibility: hidden;
        }

        .circle {
            position: absolute;
            height: 32px;
            width: 32px;
            border-radius: 16px;
            background-color: rgb(70, 209, 96);
            margin-top: 20px;
            top: -15px;
            right: 5px;
        }

        .text-circle {
            top: 4px;
            position: relative;
            font-weight: bold;
            color: white;
            text-align: center;
            user-select: none;
        }

        @media (min-width: 768px) {
            .bd-placeholder-img-lg {
                font-size: 3.5rem;
            }
        }

        @media (max-width: 768px) {
            .d-flex {
                display: block !important;
            }
        }

        @media (max-width: 500px) {
            #subreddit {
                float: unset !important;
            }

            #quantity {
                float: unset !important;
            }
        }

    </style>
    <link href="assets/album/css/album.css" rel="stylesheet">
</head>
<body>
<a href="javascript:" id="return-to-top"><i class="fas fa-chevron-up"></i></a>
<header>
    <div class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container d-flex justify-content-between">
            <a href="/" class="navbar-brand d-flex align-items-center">
                <svg xmlns="https://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor"
                     stroke-linecap="round" stroke-linejoin="round" stroke-width="2" aria-hidden="true"
                     class="mt-1 mr-2"
                     viewBox="0 0 24 24" focusable="false">
                    <image href="assets/favicon.png" width="20" height="20"/>
                </svg>
                <strong>GL Finder</strong>
            </a>
        </div>
    </div>
</header>

<main role="main">

    <div class="album py-3 bg-light">
        <div class="container">
            <div class="pb-3">
                <label for="filterword">Filter List</label>
                <input type="text" name="filterword" id="filterword" <?php echo $filter_value; ?>>
                <select class="custom-select custom-select-sm w-auto float-right" name="subreddit" id="subreddit"
                        style="vertical-align: unset;">
                    <option value='' <?php if (!isset($subreddit)) {
                        echo "selected";
                    } ?> disabled>Pick a subreddit
                    </option>
                    <?php foreach ($subreddits_array as $key => $value) {
                        if ($subreddit == $value) {
                            $selected = "selected";
                        }
                        echo "<option value='$value' $selected>r/$value</option>";
                        unset($selected);
                    } ?>
                </select>
                &nbsp;
                <select class="custom-select custom-select-sm w-auto float-right" name="quantity" id="quantity"
                        style="vertical-align: unset;">
                    <?php foreach ($entries_array as $key => $entries) {
                        if ($size == $entries) {
                            $selected = "selected";
                        }
                        echo "<option value='$entries' $selected>$entries Entries</option>";
                        unset($selected);
                    } ?>
                </select>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="w2c_only" <?php echo $w2c_only; ?>>
                    <label class="form-check-label" for="w2c_only">W2C Only</label>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4" id="main_holder">
                <?php

                foreach (results($timestamp, $subreddit, $size)['assets'] as $datum) {

                    if (isset($datum['imgur_iframe'])) {
                        $imgur_preview = $datum['imgur_iframe'];
                    }

                    if ($datum['thumbnail_link'] != null) {
                        $image = "$datum[thumbnail_link]";
                    }

                    if ($datum['w2c_link'] != null) {
                        $w2c = "$datum[w2c_link]";
                    }

                    if ($datum['gl_counter'] !== 1) {
                        $gl_counter = $datum['gl_counter'];
                    }

                    ?>
                    <div class="item col" <?php echo "data-id='$datum[reddit_link_id]'" ?>>
                        <div class="card mb-4 shadow-sm">
                            <a href="<?php echo "https://www.reddit.com$datum[reddit_link]"; ?>" target='_blank'
                               rel='noreferrer'>
                                <?php if (!isset($image)) { ?>
                                    <svg class="bd-placeholder-img card-img-top" width="100%" height="225"
                                         xmlns="https://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
                                         focusable="false"
                                         role="img" aria-label="Thumbnail">
                                        <title><?php echo "$datum[reddit_title]"; ?></title>
                                        <rect width="100%" height="100%" fill="#55595c"/>
                                        <text x="50%" y="50%" fill="#eceeef" dy=".3em">Thumbnail not available</text>
                                    </svg>
                                <?php } else { ?>
                                    <img class="bd-placeholder-img card-img-top lazy"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 3 2'%3E%3C/svg%3E"
                                         data-src="<?php echo "$image"; ?>"
                                         alt="<?php echo "$datum[reddit_title]"; ?>"
                                         title="<?php echo "$datum[reddit_title]"; ?>"
                                         height="225" width="100%" style="object-fit: cover;object-position: 50% 50%"/>
                                <?php } ?>
                            </a>

                            <div class="card-body flex-column h-100">
                                <p class="card-text"><?php echo "$datum[reddit_title]"; ?></p>
                                <div class="justify-content-between align-items-center">
                                    <small class="text-muted post_date" <?php $date = $datum['reddit_created_utc'];
                                    echo "data-timestamp='$date'";
                                    unset($date); ?>></small>
                                    <div class="btn-group float-right">
                                        <?php if (isset($imgur_preview)) { ?>
                                            <a href="<?php echo $imgur_preview; ?>"
                                               class="btn btn-sm btn-outline-secondary" target='_blank'
                                               rel='noreferrer'>Imgur</a>
                                        <?php }
                                        if (isset($w2c)) { ?>
                                            <a href="<?php echo $w2c; ?>" class="btn btn-sm btn-outline-secondary"
                                               target='_blank' rel='noreferrer'>W2C</a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <?php if (isset($gl_counter)) { ?>
                                <div class="circle">
                                    <p class="text-circle"><?php echo $gl_counter; ?></p>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php
                    unset($titulo, $image, $imgur_preview, $w2c, $gl_counter);
                    $last_timestamp = $datum['reddit_created_utc'];
                }
                unset($datum);

                ?>

            </div>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4" id="search_holder" hidden>

            </div>
        </div>
    </div>

</main>

<footer class="text-muted">
    <div class="container">
        <a id="next_page" href="<?php echo $next_item . $last_timestamp . $size_get . $w2c_uri ?>"
           data-href="<?php echo $next_item . $last_timestamp . $size_get . $w2c_uri ?>">Next</a>
    </div>
</footer>

<script src="assets/js/jquery-3.5.1.min.js"></script>
<script src="assets/js/popper.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/moment-with-locales.min.js"></script>
<script>
    $(document).ready(function () {

        function tooltip() {
            $('.circle').tooltip({
                trigger: "hover",
                placement: "top",
                title: "Total GL's"
            })
        }

        $('[data-toggle="tooltip"]').tooltip();

        $(window).scroll(function () {
            if ($(this).scrollTop() >= 50) {
                $('#return-to-top').fadeIn(200);
            } else {
                $('#return-to-top').fadeOut(200);
            }
        });
        $('#return-to-top').click(function () {
            $('body,html').animate({
                scrollTop: 0
            }, 500);
        });

        var lazyLoadInstance = new LazyLoad({
            elements_selector: ".lazy",
            use_native: true
        });

        //Filter Vars
        var typingTimer;
        var doneTypingInterval = 1000;
        var $search = $('#filterword');

        function convert_dates() {

            var locale = window.navigator.userLanguage || window.navigator.language;
            moment.locale(locale);

            var localeData = moment.localeData();
            var format = localeData.longDateFormat('L');

            $('small.post_date').each(function () {
                var date = $(this).data('timestamp');
                var m1 = moment(moment.unix(date), format);
                $(this).text(m1.format(format));
            })
        }

        function getUrlVars() {
            var vars = [], hash;
            var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
            for (var i = 0; i < hashes.length; i++) {
                hash = hashes[i].split('=');
                vars.push(hash[0]);
                vars[hash[0]] = hash[1];
            }
            return vars;
        }

        function removeURLParameter(url, parameter) {
            //prefer to use l.search if you have a location/link object
            var urlparts = url.split('?');
            if (urlparts.length >= 2) {

                var prefix = encodeURIComponent(parameter) + '=';
                var pars = urlparts[1].split(/[&;]/g);

                //reverse iteration as may be destructive
                for (var i = pars.length; i-- > 0;) {
                    //idiom for string.startsWith
                    if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                        pars.splice(i, 1);
                    }
                }

                url = urlparts[0] + '?' + pars.join('&');
                return url;
            } else {
                return url;
            }
        }

        function w2c_only() {
            var base_url = $('#next_page').data('href') + '&w2c_only=yes';

            if ($('#w2c_only').is(":checked")) {

                $('#next_page').attr('href', base_url);

                $("div.btn-group").each(function (i) {
                    var val = 'w2c';
                    var content = $(this).find('a.btn-outline-secondary').text();

                    if (content.toLowerCase().indexOf(val) == -1) {
                        $(this).parents('div.item.col').hide();
                    } else {
                        $(this).parents('div.item.col').show();
                    }
                });
            }
        }

        function doneTyping() {
            var search = $search.val();
            if (search != "") {

                $.ajax({
                    url: '?search',
                    type: 'post',
                    data: {
                        search_query: search,
                        subreddit: $('meta[subreddit]').attr('subreddit')
                    },
                    dataType: 'json',
                    success: function (response) {

                        var len = response['assets'].length;

                        $('#main_holder').prop('hidden', true);
                        $("#search_holder").empty();

                        for (var i = 0; i < len; i++) {

                            var imgur_iframe = response['assets'][i]['imgur_iframe'];
                            var reddit_created_utc = response['assets'][i]['reddit_created_utc'];
                            var reddit_link = response['assets'][i]['reddit_link'];
                            var reddit_link_id = response['assets'][i]['reddit_link_id'];
                            var reddit_title = response['assets'][i]['reddit_title'];
                            var thumbnail_link = response['assets'][i]['thumbnail_link'];
                            var w2c_link = response['assets'][i]['w2c_link'];
                            var gl_counter = response['assets'][i]['gl_counter'];

                            var thumbnail_element = '';
                            var imgur_element = '';
                            var w2c_element = '';
                            var gl_element = '';

                            if (thumbnail_link != null && thumbnail_link.length > 1) {
                                thumbnail_element = '<img class="bd-placeholder-img card-img-top lazy" src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 3 2\'%3E%3C/svg%3E" data-src="' + thumbnail_link + '" alt="' + reddit_title + '" title="' + reddit_title + '" height="225" width="100%" style="object-fit: cover;object-position: 50% 50%"/>';
                            } else {
                                thumbnail_element = '<svg class="bd-placeholder-img card-img-top" width="100%" height="225" xmlns="https://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Thumbnail"><title>' + reddit_title + '</title><rect width="100%" height="100%" fill="#55595c"/><text x="50%" y="50%" fill="#eceeef" dy=".3em">Thumbnail not available</text></svg>';
                            }

                            if (imgur_iframe != null) {
                                imgur_element = '<a href="' + imgur_iframe + '" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noreferrer">Imgur</a>';
                            }

                            if (w2c_link != null) {
                                w2c_element = '<a href="' + w2c_link + '" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noreferrer">W2C</a>';
                            }

                            if (gl_counter !== 1) {
                                gl_element = '<div class="circle"><p class="text-circle">' + gl_counter + '</p></div>';
                            }

                            var reddit_card = '<div class="item col" data-id="' + reddit_link_id + '">' +
                                '<div class="card mb-4 shadow-sm">' +
                                '<a href="https://www.reddit.com' + reddit_link + '" target="_blank" rel="noreferrer">' +
                                thumbnail_element +
                                '</a>' +
                                '<div class="card-body flex-column h-100">' +
                                '<p class="card-text">' + reddit_title + '</p>' +
                                '<div class="justify-content-between align-items-center">' +
                                '<small class="text-muted post_date" data-timestamp="' + reddit_created_utc + '"></small>&nbsp;' +
                                '<div class="btn-group float-right">' +
                                imgur_element +
                                w2c_element +
                                '</div>' +
                                '</div>' +
                                '</div>' +
                                gl_element +
                                '</div>' +
                                '</div>';

                            $("#search_holder").append(reddit_card);
                            tooltip();
                        }
                        convert_dates();
                        lazyLoadInstance.update();
                        $("#search_holder").prop('hidden', false);
                        $('#next_page').prop('hidden', true);
                    }
                });

            } else {
                $("#search_holder").prop('hidden', true);
                $('#next_page').prop('hidden', false);
                $('#main_holder').prop('hidden', false);
            }
        }

        tooltip();

        convert_dates();

        w2c_only();

        $search.on('keyup', function () {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(doneTyping, doneTypingInterval);
        });

        $search.on('keydown', function () {
            clearTimeout(typingTimer);
        });


        $('#subreddit').on('change', function () {
            var subreddit = $(this).val();
            var operator = '';

            if (window.location.href in getUrlVars()) {
                operator = '?';
            } else {
                operator = '&';
            }

            var next_url = removeURLParameter(window.location.href, 'subreddit');

            if (subreddit) {
                var url = next_url + operator;
                window.location = url + "subreddit=" + subreddit;
            }

            return false;
        });

        $('#quantity').on('change', function () {
            var quantity = $(this).val();
            var operator = '';

            if (window.location.href in getUrlVars()) {
                operator = '?';
            } else {
                operator = '&';
            }

            var next_url = removeURLParameter(window.location.href, 'size');

            if (quantity) {
                var url = next_url + operator;
                window.location = url + "size=" + quantity;
            }

            return false;
        });

        $('#w2c_only').on('change', function () {
            var base_url = $('#next_page').data('href') + '&w2c_only=yes';

            if ($(this).is(":checked")) {

                $('#next_page').attr('href', base_url);

                $("div.btn-group").each(function (i) {
                    var val = 'w2c';
                    var content = $(this).find('a.btn-outline-secondary').text();

                    if (content.toLowerCase().indexOf(val) == -1) {
                        $(this).parents('div.item.col').hide();
                    } else {
                        $(this).parents('div.item.col').show();
                    }

                });

            } else {
                $('div.item.col').show();

                var next_url = removeURLParameter(base_url, 'w2c_only');
                $('#next_page').attr('href', next_url);
            }
        });

        $('#top').on('click', function (e) {
            e.preventDefault();
        })

    });
</script>
</body>
</html>
