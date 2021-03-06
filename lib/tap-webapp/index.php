<!DOCTYPE html>
<html>
<head>
    <title>TAP Web</title>
    <meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width" />
    <meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1" media="(device-height: 568px)" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />

    <link rel="stylesheet" href="vendor/jqmobile/jquery.mobile.css" />
    <link rel="stylesheet" href="vendor/leaflet/leaflet.css" />
    <link rel="stylesheet" href="vendor/photoswipe/photoswipe.css" />
    <link rel="stylesheet" href="vendor/mediaelement/mediaelementplayer.css" />
    <link rel="stylesheet" href="dist/css/Tap-1.1.0.min.css" />

    <script>
        var TapConfig = {
            tourMLEndpoint: '<?php echo "tour_".$_GET['id'].".xml"; ?>',
            trackerID: '',
            media: {
                pluginPath: 'dist/vendor/mediaelement/'
            },
            geo: {},
            social: {},
            tourSettings: {
                'default': {
                    'defaultNavigationController': 'StopListView',
                    'enabledNavigationControllers': ['StopListView', 'KeypadView', 'MapView']
                }
            },
            navigationControllers: {},
            viewRegistry: {},
            primaryRouter: "Default"
        };
    </script>
    
    <script src="dist/js/TAP-Web-App-1.1.0-with-dependencies.js"></script>
    <script>
        $(function(){
            var app = new TapAPI.classes.views.AppView();
            app.runApp();
        })
    </script>
</head>
<body>
</body>
</html>
