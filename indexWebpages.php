<?php $title='515 Search Engine: Index Webpages'; include("header.php");?>

<div class="container">
    <div class="row">
        <div class="col">
            <h1 class="text-light text-center">Index Webpages</h1>
        </div>
    </div>

    <hr class="bg-light">

    <div class="row">
        <form method="POST" action="scripts/crawl.php">
        
        <!-- -------------------------------------------------- SEED URL ---------------------------------------------------------- -->
        <div class="row">
            <div class="col">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Seed URL</span>
                    </div>
                    <input class="form-control" type="text" name="seedUrl" placeholder="eg. https://www.geeksforgeeks.org/php-tutorials/" required>
                </div>
            </div>
        </div>
        <br>

        <!-- ----------------------------------- MAX PAGES AND EXECUTION TIME ----------------------------------------------------- -->
        <div class="row">
            <div class="col">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="my-addon">Max Pages</span>
                    </div>
                    <input class="form-control" type="text" name="maxPages" placeholder="<= 500" required>
                </div>
            </div>

            <div class="col">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="my-addon">Max Execution Time</span>
                    </div>
                    <input class="form-control" type="text" name="maxTime" placeholder="<= 300 s" required>
                </div>
            </div>
        </div>
        <br>
        <!-- --------------------------- LOGIN ------------------------------ -->
        <div class="row">
            <div class="col">
                <input class="form-control" type="text" name="username" placeholder="Username" required>
            </div>

            <div class="col">
                <input class="form-control" type="text" name="password" placeholder="Password" required>
            </div>
        </div>
        <br>
                    
        <div class="row justify-content-end">
            <div class="col">
                <div class="float-end">
                    <input class="btn btn-lg btn-secondary" type="submit" value="Index Webpages">
                </div>                
            </div>
        </div>
    
        </form>
    </div>
</div>

<?php include("footer.php"); ?>