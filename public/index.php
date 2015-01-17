<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>DataTables example</title>
    <link rel="stylesheet" href="//cdn.datatables.net/1.10.4/css/jquery.dataTables.min.css"/>
    <script type="text/javascript" language="javascript" src="//code.jquery.com/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" language="javascript" src="//cdn.datatables.net/1.10.4/js/jquery.dataTables.min.js"></script>
</head>
<body id="dt_example">
<div id="container">
    <h2>Datatables Examples - full list</h2>
    <?php
    $files = glob('examples/*', GLOB_BRACE);
    foreach ($files as $file)
    {
        ?>
        <p><a href='http://datatables.app/<?php echo $file; ?>/'><?php echo $file; ?></a></p>
        <?php
    }
    ?>
</div>
</body>
</html>