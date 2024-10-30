/**
 * render iframe in pure javascript
 * @type {{render}}
 */
var Iframe = function () {
    var frames;

    /**
     * init iframe
     */
    var initFrame = function () {
        frames = document.getElementsByClassName('kursolino_frame');

        if (frames.length) {
            for (var f = 0; f < frames.length; f++) {
                // get frame
                var frame = frames[f],
                    id = 'kursolino_frame_' + f;

                // continue, if frame was already initiated
                if (frame.id === id) {
                    continue;
                }

                // set frame id
                frame.id = id;

                // set frame base styles
                frame.width = '100%;';
                frame.height = '800';
                frame.scrolling = 'no';
                frame.frameborders = 'no';
                frame.allowtransparency = 'true';
                frame.setAttribute('data-referer', location.href);
            }

            // init frame communication
            initCommunication();
        }
    };

    /**
     * detect frame by event
     * @param event
     * @returns {*}
     */
    var getFrameByEvent = function (event) {
        return [].slice.call(document.getElementsByTagName('iframe')).filter(function (iframe) {
            return iframe.contentWindow === event.source;
        })[0];
    };

    /**
     * init communication between iframe and parent
     */
    var initCommunication = function () {
        // listener method
        var c = function (e) {
            var frame = getFrameByEvent(e);
            if (frame) {
                communicate(frame, e);
            }
        };

        // set event listener
        if (window.addEventListener) {
            window.addEventListener('message', c, false);
        } else if (window.attachEvent) {
            window.attachEvent('onmessage', c);
        }

        // set current scroll height
        var s = function () {
            if (frames.length) {
                for (var f = 0; f < frames.length; f++) {
                    // get frame
                    var frame = frames[f];

                    // get offsets
                    var topOffset = frame.getBoundingClientRect().top + window.scrollY;
                    var currentScroll = document.scrollingElement.scrollTop;

                    // send offset
                    frame.contentWindow.postMessage({
                        cmd: 'scroll',
                        offset: currentScroll - topOffset
                    }, '*')
                }
            }
        };

        if (window.addEventListener) {
            window.addEventListener('scroll', s, false);
        } else if (window.attachEvent) {
            window.attachEvent('onscroll', s);
        }
    };

    /**
     * communication between iframe and parent
     * @param frame
     * @param event
     */
    var communicate = function (frame, event) {
        if (event.data.cmd === 'height') {
            frame.style.height = event.data.h + "px";
        } else if (event.data.cmd === 'scroll') {
            console.log(event.data);
        } else if (event.data.cmd === 'get-referer') {
            frame.contentWindow.postMessage({
                referer: frame.getAttribute('data-referer')
            }, '*')
        }
    };

    /**
     * public methods
     */
    return {
        render: function () {
            initFrame();
        }
    }
}();

// start rendering iframe
Iframe.render();