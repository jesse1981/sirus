<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
    
    <?php include 'head.php'; ?>
    
    <body>
        
        <div id="page-wrap">
            
            <?php include 'header.php' ?>

            <?php include 'header-nav.php'; ?>
            
            <?php include 'side-nav.php'; ?>
            
            <div id="main-content">
                <?php 
                echo "EXTRAS<BR/>";
                echo "MODULE = ".MODULE."<br/>";
                echo "ACTION = ".ACTION."<br/>";
                echo "ID = ".ID."<br/>";
                echo "FORMAT = ".FORMAT."<br/>";
                var_dump($_GET);
                ?>
            </div>
            
            <?php include 'footer.php'; ?>

        </div>
        
    </body>
</html>