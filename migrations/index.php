<?php
require_once './MigrationFileManager.php';
$fileManager = new MigrationFileManager;
$config = $fileManager->readConfig();
if (!$config->visibility)
    require_once './error_404.php';

$fileManager->createMigrationTable();
$files = $fileManager->getFileList();

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<!doctype html>
<html lang="en">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css" />
        <title>Data Migrations</title>
    </head>
    <body>
        <div class="container" style="margin-top:20px;">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">File Name</th>
                        <th scope="col">Action Date</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody
                <?php foreach ($files as $file): $status = $fileManager->checkMigrationStatus($file, $fileManager->getProcessedFiles()); ?>
                        <tr>
                            <td><?= $status['file_name'] ?></td>
                            <td><?= $status['applied_at'] ?></td>
                            <td>
                                <?php if ($status['status']): ?>
                                    <a href="javascript:"><i class="fa fa-check-circle"></i></a>
                                <?php else: ?>
                                    <a href="javascript:" data-toggle="tooltip" title="Run Migration" data-val="<?= $status['file_name'] ?>" class="applyMigration"><i class="fa fa-play"></i></a>
                                    <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="col">File Name</th>
                        <th scope="col">Action Date</th>
                        <th scope="col">Action</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- Optional JavaScript -->
        <!-- jQuery first, then Popper.js, then Bootstrap JS -->
        <script
            src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
        crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
        <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
        <script>
            $(document).ready(function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('table').DataTable();
                $('table').on('click', '.applyMigration', function () {
                    var elem = this;
                    $.post('./applyMigration.php', {file_name: $(this).attr('data-val')}, function (data) {
                        var res = $.parseJSON(data);
                        if (res.success) {
                            $(elem).find("i").removeClass("fa-play").addClass("fa-check-circle")
                                    .parent().attr('title', 'Migration Applied!').attr('data-original-title', 'Migration Applied!').tooltip('show').removeClass('applyMigration');
                            alert(res.message);
                        } else {
                            alert(res.message);
                        }
                    });
                });
            });

        </script>
    </body>
</html>
