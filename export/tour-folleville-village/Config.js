define([], function() {
    return {
        tourMLEndpoint: 'tour.xml',
        trackerID: '',
        media: {
            pluginPath: '../../lib/tap-webapp/dist/vendor/mediaelement/'
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