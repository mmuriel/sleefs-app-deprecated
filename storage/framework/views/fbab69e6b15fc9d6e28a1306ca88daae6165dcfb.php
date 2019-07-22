<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e(config('app.name', 'Sleefs App')); ?></title>

    <style>
        td,th {
            border-left: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 0.5rem;
        }
    </style>
</head>
<body>
    <table style="width:500px; font-size: 16px; border-top: 1px solid #000; border-right: 1px solid #000;">
        <tr>
            <th>PO</th>
            <th>SKU</th>
            <th>Position</th>
            <th>Quantity</th>
            <th>Before (Qty)</th>
        </tr>
        <?php echo $htmlToPrint; ?>

    </table>
</body>
</html>