<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Redirection Links Parser</title>

    <!-- Bootstrap Core CSS -->
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css" type="text/css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/t/dt/dt-1.10.11/datatables.min.css"/>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css" type="text/css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body>

<header>
    <div class="container">
        <h1>
            Redirection Links Parser
        </h1>
    </div>
</header>

<section id="main">
    <div class="container">
        <div class="well">
            <div class="form-group">
                <label for="parser_type">What do we parse?</label>
                <select name="parser_type" id="parser_type" class="form-control">
                    <option value="">-- Select --</option>
                    <option value="prweb">PRWeb</option>
                    <option value="proz">PROZ</option>
                    <option value="getapp">GetApp</option>
                    <option value="auriga">Agenzia ICE (auriga.ice.it)</option>
                </select>
            </div>

            <div class="alert alert-warning" role="alert" style="display: none;">
                Attention! Do not close the page, browser or stop the script after pressing the submit button!
            </div>

            <div id="prweb" class="source" style="display: none;">
                <form action="functions/process.php" method="post" class="form-inline">
                    <div class="form-group" style="margin-right: 10px;">
                        <label for="prweb_urls">Enter one URL for prweb.com per row</label>
                        <textarea name="url[prweb]" id="prweb_urls" class="form-control" cols="55" rows="10" placeholder="http://www.prweb.com/news/20161001/index.htm"></textarea>
                    </div>

                    <div class="form-group" style="margin-left: 10px;">
                        <input type="submit" class="btn btn-primary">
                    </div>
                </form>
            </div>

            <div id="proz" class="source" style="display: none;">
                <form action="functions/process.php" method="post" class="form-inline">
                    <div class="form-group" style="margin-right: 10px;">
                        <label for="proz_url">Enter the URL for proz.com</label>
                        <input name="url[proz]" id="proz_url" class="form-control" placeholder="http://www.proz.com/translation-agencies" style="width: 400px;" />
                    </div>

                    <div class="form-group" style="margin-left: 10px;">
                        <input type="submit" class="btn btn-primary">
                    </div>
                </form>
            </div>

            <div id="getapp" class="source" style="display: none;">
                <form action="functions/process.php" method="post" class="form-inline">
                    <div class="form-group" style="margin-right: 10px;">
                        <label for="getapp_url">Enter the URL for getapp.com</label>
                        <input name="url[getapp]" id="getapp_url" class="form-control" placeholder="https://www.getapp.com/browse" style="width: 400px;" />
                    </div>

                    <div class="form-group" style="margin-left: 10px;">
                        <input type="submit" class="btn btn-primary">
                    </div>
                </form>
            </div>

            <div id="auriga" class="source" style="display: none;">
                <form action="functions/process.php" method="post" class="form-inline">
                    <div class="form-group" style="margin-right: 10px;">
                        <label for="auriga_url">Enter the URL for auriga.ice.it</label>
                        <input
                            name="url[auriga]"
                            id="auriga_url"
                            class="form-control"
                            placeholder="http://auriga.ice.it/opportunitaaffari/offertaitaliana/web_new/RispostaRicercaSalvata.asp"
                            value="http://auriga.ice.it/opportunitaaffari/offertaitaliana/web_new/RispostaRicercaSalvata.asp"
                            style="width: 400px;" />
                    </div>

                    <div class="form-group" style="margin-left: 10px;">
                        <input type="submit" class="btn btn-primary">
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<div id="loader"><img src="assets/img/loader.gif" /></div>

<!-- jQuery -->
<script src="assets/vendor/jquery/jquery-1.11.2.min.js"></script>

<!-- Bootstrap Core JavaScript -->
<script src="assets/vendor/bootstrap/js/bootstrap.min.js"></script>

<!-- DataTables JavaScript -->
<script type="text/javascript" src="https://cdn.datatables.net/t/dt/dt-1.10.11/datatables.min.js"></script>

<!-- Custom JavaScript -->
<script src="assets/js/script.js"></script>

<script>
    $(document).ready(function() {
        $('table').DataTable();

        // Listen to parser type select
        $(document).on('change', 'select[name="parser_type"]', function() {
            var
                $this = $(this),
                $alert = $('.alert-warning'),
                $source = $('.source');

            $alert.slideDown();
            $source.slideUp();
            $('#' + $this.val()).slideDown();
        });
    });
</script>

</body>

</html>
