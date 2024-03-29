<?php

namespace App\Custom;

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

class MediaManager
{

  private $upload;

  function __construct()
  {
    Configuration::instance(getenv("CLOUDINARY_URL"));
    $this->upload = new UploadApi();
  }

  function uploadMedia($media_type, $image_string, $folder)
  {
    if ($media_type == "image") {
      return $this->upload->upload($image_string, [
        "overwrite" => TRUE,
        "folder" => $folder
      ]);
    }
  }

  function deleteMedia($media_type, $public_id)
  {
    return $this->upload->destroy($public_id, ["resource_type" => $media_type]);
  }
}
