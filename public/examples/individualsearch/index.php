<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>DataTables example</title>
    <link rel="stylesheet" href="//cdn.datatables.net/1.10.4/css/jquery.dataTables.min.css"/>
    <script type="text/javascript" language="javascript" src="//code.jquery.com/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" language="javascript" src="//cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function() {
            // Setup - add a text input to each footer cell
            $('#example tfoot th').each( function () {
                var title = $(this).text();
                $(this).html( '<input type="text" placeholder="Search '+title+'" />' );
            } );

            var table = $('#example').DataTable( {
                "serverSide": true,
                "ajax" : "ajax.php",
                "columns": [
                    { "searchable": false }, // id column can't be searched.
                    null,
                    null
                ]
            } );

            // Apply the search
            table.columns().every( function () {
                var that = this;

                $( 'input', this.footer() ).on( 'keyup change', function () {
                    if ( that.search() !== this.value ) {
                        that
                            .search( this.value )
                            .draw(false);
                    }
                } );
            } );
        } );
    </script>
    <style>
        tfoot input {
            width: 100%;
            padding: 3px;
            box-sizing: border-box;
        }
    </style>
</head>
<body id="dt_example">
<div id="container">
    <h1>Datatables - individual search example</h1>
    <table border="0" cellpadding="4" cellspacing="0" class="display" id="example">
        <thead>
        <tr>
            <th width="8%">Film ID</th>
            <th width="32%">Title</th>
            <th width="60%">Description</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>loading...</td>
        </tr>
        </tbody>
        <tfoot>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Description</th>
        </tr>
        </tfoot>

    </table>
</div>
</body>
</html>