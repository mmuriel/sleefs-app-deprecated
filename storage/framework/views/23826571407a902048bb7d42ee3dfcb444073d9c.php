<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title><?php echo $__env->yieldContent('page_title'); ?></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" href="icon.png">
        <!-- Place favicon.ico in the root directory -->

        <link rel="stylesheet" href="<?php echo e($app['url']->to('/')); ?>/css/icomoon.css">
        <link rel="stylesheet" href="<?php echo e($app['url']->to('/')); ?>/css/normalize.css">
        <link rel="stylesheet" href="<?php echo e($app['url']->to('/')); ?>/css/main.css">

        <script
              src="https://code.jquery.com/jquery-2.2.4.min.js"
              integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44="
              crossorigin="anonymous">
        </script>
    </head>
    <body>
        <!--[if lte IE 9]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
        <![endif]-->

        <!-- Add your site or application content here -->
        <div id="app">
            
            <?php $__env->startComponent('snippets.header'); ?>
                <?php echo e($app['url']->to('/')); ?>

            <?php echo $__env->renderComponent(); ?>
            <!-- Se muestra el contenido -->
            <?php echo $__env->yieldContent('content'); ?>
            <!-- Se muestra el footer -->
            <?php $__env->startComponent('snippets.footer'); ?>
                <br />
            <?php echo $__env->renderComponent(); ?>
        </div>
        <script src="<?php echo e($app['url']->to('/')); ?>/js/app-sleefs.js"></script>
    </body>
</html>