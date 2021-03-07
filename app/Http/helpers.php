<?php

function current_disk()
{
    $current_disk = \Illuminate\Support\Facades\Storage::disk('public');
    return $current_disk;
}

function media_url($img)
{
    $url_path = '';

    if ($img) {
        if ($img->type == 'image') {
            if ($img->storage == 'public') {
                $url_path = asset('uploads/images/' . $img->media_name);
            } elseif ($img->storage == 's3') {
                $url_path = \Illuminate\Support\Facades\Storage::disk('s3')->url('uploads/images/' . $img->media_name);
            }
        }
    } else {
        $url_path = asset('uploads/placeholder.png');
    }

    return $url_path;
}

function str_random($val)
{
    $str_random = \Illuminate\Support\Str::random($val);
    return $str_random;
}

function str_slug($val)
{
    $str_slug = \Illuminate\Support\Str::slug($val);
    return $str_slug;
}
