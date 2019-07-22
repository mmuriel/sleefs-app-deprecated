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
        <div id="app-podetails">
            <table class="po_details">
                <tr>
                    <td>Report Print Date</td>
                    <td><?php echo e(date("Y-m-d H:i:s")); ?></td>
                </tr>
                <tr>
                    <td>PO ID</td>
                    <td><?php echo e($po->po_id); ?></td>
                </tr>
                <tr>
                    <td>PO Number</td>
                    <td><?php echo e($po->po_number); ?></td>
                </tr>
                <tr>
                    <td>PO Created Date</td>
                    <td><?php echo e($poextended->created_at); ?></td>
                </tr>
                <tr>
                    <td>PO Expected Date</td>
                    <td><?php echo e($poextended->po_date); ?></td>
                </tr>
                <tr>
                    <td>Vendor Name</td>
                    <td><?php echo e($poextended->vendor_name); ?></td>
                </tr>
                <tr>
                    <td>Vendor Email</td>
                    <td><?php echo e($poextended->vendor_email); ?></td>
                </tr>
                <tr>
                    <td>Vendor Account Number</td>
                    <td><?php echo e($poextended->vendor_account_number); ?></td>
                </tr>
            </table>
            <h2>PO Items</h2>
            <table class="po_items">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Item Price</th>
                        <th>Total Price</th>
                        <th>Image</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($po->items->isEmpty()): ?>
                    <tr>
                        <td colspan="6">There aren't items on this PO</td>
                    </tr>
                    <?php else: ?>
                        <?php $__currentLoopData = $po->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $item->poItemListView; ?>

                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <script src="js/app-sleefs.js"></script>
    </body>
</html>