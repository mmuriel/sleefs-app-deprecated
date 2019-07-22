<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sleefs App') }}</title>

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
        {!! $htmlToPrint !!}
    </table>
</body>
</html>