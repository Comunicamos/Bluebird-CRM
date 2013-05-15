HummingbirdTracker = {
  track: function(env) {
    if(typeof(env) == "undefined") { env = {}; }

    // send some miscellaneous info about the request
    env.u = document.location.href;
    env.bw = window.innerWidth;
    env.bh = window.innerHeight;

    // example of sending a cookie named 'guid'
    // env.guid = (document.cookie.match(/guid=([^\_]*)_([^;]*)/) || [])[2];

    if(document.referrer && document.referrer != "") {
      env.ref = document.referrer;
    }

    env.rnd = Math.floor(Math.random() * 10e12);

    var params = [];
    for(var key in env) {
      if(env.hasOwnProperty(key)) {
        // console.log(key,env);
        // console.log(env.hasOwnProperty(key));
        params.push(encodeURIComponent(key) + "=" + encodeURIComponent(env[key]));
      }
    }

    // replace 'localhost:8080' with hummingbird's URL
    var img = new Image();

    //Hummingbird's PORT HAS TO BE THE SAME 
    img.src = 'http://localhost:8000/tracking_pixel.gif?' + params.join('&');
  },
  assign: function(env) {
    if(typeof(HummingbirdEnv) === "undefined") {
      window.HummingbirdEnv = {};
    }
    HummingbirdEnv = cj.extend({}, HummingbirdEnv, env);
  },
  setActiveWatch: {
    start: function(){
      // turns on the timer & binding
      this.bind();
      this.timer.setIntervalTimer();
    },
    stop: function(){
      this.unbind();
      this.timer.resetIntervalTimer();
    },
    page: cj(document),
    // mousemoveTrackIsActive: false,
    // keydownTrackIsActive: false,
    // clickTrackIsActive: false,
    eventTypes: ["mousemove","keydown","click"],
    bind: function() {
      var that = this;
      cj.each(this.eventTypes, function(i, eventType){
        // that.setTrackerStatus(eventType, true);
        that.page.on(eventType, function(event) {
          that.timer.eventHappened = true;
          // console.log(event);
        });
      });
    },
    unbind: function() {
      var that = this;
      cj.each(this.eventTypes, function(i, eventType) {
          // that.setTrackerStatus(eventType, false);
          that.timer.eventHappened = false;
          that.page.off(eventType, function() {});
      });
    },
    /*setTrackerStatus: function(eventType, bool) {
      switch(eventType) {
        case 'mousemove': this.mousemoveTrackIsActive = bool; break;
        case 'keydown': this.keydownTrackIsActive = bool; break;
        case 'click': this.clickTrackIsActive = bool; break;
      }
    },*/
    timer: {
      intervalId: null, // this is the setInterval pointer...
      eventHappened: true, //this will update and be pulled on every websocket pulse
      timeSinceLastReset: 0,
      idleInterval: 1000, //timeBeforeDeclaringIdle in MS, aka 60 seconds
      setIntervalTimer: function(){
        var that = this;
        this.intervalId = setInterval(function() {
          if(that.eventHappened === true) {
            // console.log('Event Happened');
            that.eventHappened = false;
            that.timeSinceLastReset = 0;
          }
          else {
            // console.log('No Event Happened');
            that.eventHappened = false;
            that.timeSinceLastReset += that.idleInterval;
          }
          // console.log(that);
        }, this.idleInterval);
        // to use the pulse, you'd add change the interval to pulse & on check value add
        // pulse to time since last reset until tSLR > idleInterval
      },
      resetIntervalTimer: function() {
        clearInterval(this.intervalId);
      }
    }
  }
};