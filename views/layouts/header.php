<?php
// views/layouts/header.php

// Eğer $primaryColor tanımlanmamışsa varsayılan mavi olsun (Hata önleyici)
$themeColor = isset($primaryColor) ? $primaryColor : '#0d6efd';

// Sayfa başlığı yoksa varsayılan başlık
$pageTitle = isset($pageTitle) ? $pageTitle : $event['title'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <link rel="stylesheet" href="../../public/assets/css/style.css">

    <style>
        :root {
            --theme-color: <?= $themeColor ?>;
        }
    </style>
</head>
<body>