<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>MailUp Demo Client</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="http://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.0/dist/jquery.validate.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="assets/js/scripts.js"></script>
    <script src="assets/js/timer.js"></script>
</head>
<body>
    <?php require_once 'src/bootstrap.php' ?>
    <div class="container">

        <div class="bd-pageheader text-center text-sm-left">
            <h1><strong>MailUp Demo Client</strong></h1>
        </div>

        <h3><strong>Authentication</strong></h3>
        <div class="row">
            <div class="col-sm-3">
                <div class="panel panel-default auth-panel">
                    <div class="panel-heading">Authorization code grant</div>
                    <div class="panel-body">
                        <div class="auth-panel-sign">
                            <form class="form-inline" action="index.php" method="POST">
                                <div class="form-group row">
                                    <div class="col-sm-6">
                                        <input type="submit" name="logon_by_key" class="form-control" value="Sign in to MailUp">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-9">
                <div class="panel panel-default auth-panel">
                    <div class="panel-heading">Password grant</div>
                    <div class="panel-body">
                        <form id="auth-form" action="index.php" method="POST">
                            <div class="form-group">
                                <label for="username">Username:</label>
                                <input type="text" name="username" id="username" class="form-control" placeholder="Input your MailUp username">
                            </div>
                            <div class="form-group">
                                <label for="password">Password:</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Input your MailUp password">
                            </div>
                            <div class="row">
                                <div class="col-sm-5">
                                    <input type="submit" name="logon_by_password" class="form-control btn btn-success" value="Sign in to MailUp with username and password">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-10">
                <div class="example-body">
                    <?php if($mailUp->getAccessToken() === null): ?>
                        <div><b>Unauthorized</b></div>
                    <?php else: ?>
                        <div><b>Authorized.</b></div>
                        <div><?php echo "<b>Token</b>: " . $mailUp->getAccessToken(); ?></div>
                    <?php endif; ?>
                    <?php if(isset($error)): ?>
                        <div>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
                    $token_time = $mailUp->getTokenTime();
                    if(null !== $token_time):
                ?>
                    <span id="unix-time"><?php echo $token_time; ?></span>
                    <b>Expires in: </b><span id="token-time"></span>
                <?php endif; ?>
            </div>
            <?php if($mailUp->getAccessToken() !== null): ?>
                <div class="col-sm-2 right">
                    <form class="form-inline" action="index.php" method="POST">
                        <input type="submit" name="refresh_token" class="form-control btn btn-success" value="Refresh token">
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <h3><strong>Custom method call</strong></h3>
        <div class = "panel panel-default">
            <div class="panel-body">
                <form action="index.php" method="POST">
                    <div class="form-group row">
                        <div class="col-xs-2">
                            <label for="method">Verb</label>
                            <select id="method" name="method" class="form-control">
                                <option selected value="GET">GET</option>
                                <option value="POST">POST</option>
                                <option value="PUT">PUT</option>
                                <option value="DELETE">DELETE</option>
                            </select>
                        </div>
                        <div class="col-xs-2">
                            <label for="content_type">Content-Type</label>
                            <select id="content_type" name="content_type" class="form-control">
                                <option selected value="JSON">JSON</option>
                                <option value="XML">XML</option>
                            </select>
                        </div>
                        <div class="col-xs-2">
                            <label for="url">Endpoint</label>
                            <select id="url" name="url" class="form-control">
                                <option selected value="<?php echo $mailUp->getConsoleUrl(); ?>">Console</option>
                                <option value="<?php echo $mailUp->getMailStatsUrl(); ?>">MailStatistics</option>
                            </select>
                        </div>
                        <div class="col-xs-6">
                            <label for="endpoint">Path</label>
                            <input type="text" id="endpoint" name="endpoint" value="/Console/Authentication/Info" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="body">Body</label>
                        <textarea id="body" class="form-control" name="body" rows="6" ></textarea>
                    </div>
                    <input type="submit" class="btn btn-success" name="execute_request" value="Call Method">
                    <p id="result-string"><?php if ($result !== null) echo $result; ?></p>
                </form>
            </div>
        </div>

        <h3><strong>Run example set of calls</strong></h3>
        <?php foreach ($examples_text as $number => $text): ?>
            <div class="panel-group">
                <div class = "panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a data-toggle="collapse" href="#example-<?php echo $number?>" class="collapsed" aria-expanded="false"><?php echo $text; ?></a>
                        </h4>
                    </div>
                    <div id="example-<?php echo $number?>" class="panel-collapse collapse" aria-expanded="true">
                        <form class="form-inline" action="index.php" method="POST">
                            <input type="submit" name="example_<?php echo $number?>" class="form-control btn btn-success" id="example" value="<?php echo $text; ?>">
                        </form>
                        <?php
                        if(isset($examples['example_' . $number])):     
                            $example = $examples['example_' . $number];
                        ?>
                            <?php for ($i = 0; $i < count($example); $i++): ?>
                                <div class="spoiler-wrap disabled">
                                    <div class="spoiler-head">
                                        <?php echo $example[$i]['text']; ?>
                                    </div>
                                    <div class="spoiler-body" style="display: none;">
                                        <div class="form-group row">
                                            <div class="col-xs-2">
                                                <label>Verb</label>
                                                <span class="form-control example-body"><?php echo $example[$i]['method']; ?></span>
                                            </div>
                                            <div class="col-xs-2">
                                                <label>Content-Type</label>
                                                <span class="form-control example-body"><?php echo $example[$i]['content_type']; ?></span>
                                            </div>
                                            <div class="col-xs-2">
                                                <label>Endpoint</label>
                                                <span class="form-control example-body"><?php echo $example[$i]['url']; ?></span>
                                            </div>
                                            <div class="col-xs-6">
                                                <label>Path</label>
                                                <span class="form-control example-body"><?php echo $example[$i]['endpoint']; ?></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Body</label>
                                            <div class="form-control example-body"><?php echo DataFilter::sanitizeString($example[$i]['req_body']); ?></div>
                                        </div>
                                        <div class="well">
                                            <div class="form-group example-body">
                                                <label>Response</label>
                                                <div><?php echo DataFilter::sanitizeString($example[$i]['res_body']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                            <?php if(isset($errors['example_' . $number])): ?>
                                <div class="error-answer">
                                    <?php 
                                    echo "<strong>Error code: ". $errors['example_' . $number]['code'] . "<br/>"; 
                                    echo "Message: " . $errors['example_' . $number]['message'] . "<br/>";
                                    echo "URL: " . $errors['example_' . $number]['url'] . "</strong>"; 
                                    ?>
                                </div>
                            <?php else: ?> 
                                <div class="successfully-answer"><strong>Example methods completed successfully</strong></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>

    </div>
</body>
</html>