<?php 

    define('GOOGLE_CLIENT_ID', 'REPLACE_ME');
    define('GOOGLE_CLIENT_SECRET', 'REPLACE_ME');

    require_once 'vendor/autoload.php';

    $token = [
        'access_token' => 'REPLACE_ME',
        'refresh_token' => 'REPLACE_ME',
        'token_type' => 'Bearer',
        'expires_in' => '3600',
        'created' => REPLACE_ME
    ];
    
    $youtube_id = 'REPLACE_ME';
    $thumbnail_path = realpath('thumbnail.jpg');


    /*
    // Doing the exact same request in cURL works fine.
    $data = [
        'filedata' => new CURLFile($thumbnail_path, 'image/jpeg', basename($thumbnail_path)),
    ];      
    // Execute remote upload
    $curl = curl_init();
    
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer '.$token['access_token'],
    ]);
    
    curl_setopt($curl, CURLOPT_URL, 'https://www.googleapis.com/upload/youtube/v3/thumbnails/set?videoId='.$youtube_id.'&key='.GOOGLE_CLIENT_SECRET);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    
    curl_setopt($curl, CURLOPT_VERBOSE, 1);
    $response = curl_exec($curl);
    curl_close($curl);
    echo $response;
    */



    // Based on php sample from https://developers.google.com/youtube/v3/docs/thumbnails/set
    $client = new Google_Client();   
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setScopes([
        'https://www.googleapis.com/auth/yt-analytics.readonly', 
        'https://www.googleapis.com/auth/youtube', 
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
    ]);

    $client->setAccessType('offline');	

    // If you want to use a refresh token instead to get an access token from it.
    //$token = $client->fetchAccessTokenWithRefreshToken($refresh_token);
    //var_dump($token);
    $client->setAccessToken($token);
    
    $service = new Google_Service_YouTube($client);

    try
    {
        // Specify the size of each chunk of data, in bytes. Set a higher value for
        // reliable connection as fewer chunks lead to faster uploads. Set a lower
        // value for better recovery on less reliable connections.
        $chunkSizeBytes = 1 * 1024 * 1024;
    
        // Create a MediaFileUpload object for resumable uploads.
        // Parameters to MediaFileUpload are:
        // client, request, mimeType, data, resumable, chunksize.
        $client->setDefer(true);
        $request = $service->thumbnails->set($youtube_id);
        $client->setDefer(false);
        $mimeType = 'image/jpeg';

        $media = new Google_Http_MediaFileUpload(
            $client,
            $request,
            $mimeType,
            null,
            true,
            $chunkSizeBytes
        );

        $filesize = filesize($thumbnail_path);
        echo "Filesize: $filesize\n";
        $media->setFileSize($filesize);

        // Read the media file and upload it chunk by chunk.
        $status = false;
        $handle = fopen($thumbnail_path, "rb");
        while (!$status && !feof($handle))
        {
            $chunk = fread($handle, $chunkSizeBytes);
            $status = $media->nextChunk($chunk); // The line where the Google_Service_Exception exception is thrown. 
        }

        fclose($handle);

        echo "Thumbnail uploaded success\n";                              
    }
    catch (Google_Service_Exception $err)
    {
        echo $err->getMessage();
    }
    catch (Exception $err)
    {
        echo $err->getMessage();
    }
