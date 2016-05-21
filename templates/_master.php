<!DOCTYPE html>

<!-- <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd"> -->
<html>
    
    <?php include 'templates/head.php'; ?>
    
    <body id="<?php echo MODULE; ?>">
        
        <div id="page-wrap">
            
            <?php include 'templates/header.php' ?>
            
            <!-- Outer Div -->
            <div class="outer-div" style="width: 100%">

                <!-- Inner Div -->
                <div>
                    <div id="main-content">
                        <?php echo $content; ?>
                    </div>
                </div>
            </div>
            
            <?php include 'templates/footer.php'; ?>

        </div>
    </body>
</html>