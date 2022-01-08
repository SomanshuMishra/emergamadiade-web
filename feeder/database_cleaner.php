<?php
$servername = "localhost";
$username = "Connnn";
$password = "Karama@123";
$dbname = "emerger";

// Create connection to postgress database
$conn = pg_connect("host=servername, dbname=emerger, user=Connnn, password=Karama@123");

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}else{ 
    echo "Connection Succesful";
}

// Call the API getting data from Reddit
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

//Get data with Title, Images, Previews of Imgur
function get_submission($link_id)
{
    $url = "https://api.pushshift.io/reddit/submission/search/?ids=$link_id&fields=id,title,thumbnail,media,preview,url,selftext,link_flair_text";
    return get_content($url);
}

for ($i = 0; $i < 10; $i++) { 
 
    $URL = "https://api.pushshift.io/reddit/search/comment/?q=GL&subreddit=$subreddit&before=$timestamp&sort=desc&size=$size&fields=link_id,created_utc,permalink,id";
    #Database Values.
    $reddit_linkid_sql = "SELECT reddit_link_id FROM Feeds"; 
    $reddit_link_id = pg_query($conn, $reddit_linkid_sql);

    $data = json_decode(get_content($URL), true);

    foreach ($data['data'] as $datum) {
        $datum['permalink'] = str_replace($datum['id'] . '/', '', $datum['permalink']);
        $titulo = explode('/', $datum['permalink']);
        $titulo = str_replace('/', '', str_replace('_', ' ', $titulo[5]));


        $no_dupes[] = array("reddit_link_id" => $datum['link_id'], "reddit_link" => $datum['permalink'], "reddit_title" => $titulo, "reddit_created_utc" => $datum['created_utc'], "imgur_iframe" => null, "thumbnail_link" => null, "w2c_link" => null);
        $timestamp = $datum['created_utc'];
        unset($titulo);
    }
    unset($data, $datum);

    if (!empty($no_dupes)) {
        $unique_arr = unique_key($no_dupes, 'reddit_link_id');
        $ids_arr = [];
        foreach ($unique_arr as $ids) {
            $ids_arr[] = $ids['reddit_link_id'];
        }
        unset($ids);

        $link_ids = implode(",", $ids_arr);

        $submissions_arr = json_decode(get_submission($link_ids), true);
    #compare the link ID's
        if (in_array($reddit_link_id, $submissions_arr['data']['id'])) {
            $delete_sql = "DELETE FROM Feeds WHERE reddit_link_id=$reddit_link_id";
            if(pg_query($conn, $delete_sql)){
                echo "Record deleted succesfully";
            }else{
                echo "Delete Unsuccesful";
            }
        }
    }
}

$conn->close();

?>

