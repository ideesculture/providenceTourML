define([], function() {
    return {
        tourMLEndpoint: '../export/tour-folleville-village/tour.xml',
        trackerID: '',
        media: {
            pluginPath: 'dist/vendor/mediaelement/'
        },
        geo: {},
        social: {},
        tourSettings: {
            'tour-1': {
                'defaultNavigationController': 'StopListView',
                'enabledNavigationControllers': ['StopListView', 'MapView']
            }
        },
        navigationControllers: {},
        viewRegistry: {}
    };
});