;
/**
 * A graph is a set nodes connected with edges.
 *
 * HTML given
 *
 * <.graphapi>
 *   <.graphapi-nodes> (<.graphapi-node>)*
 *   <edges> (<edge>)*
 * </>
 *
 * After setup
 * <.graphapi>
 *   <canvas>
 *   <.graphapi-nodes> (<.graphapi-node>)*
 *   <edges> (<edge>)*
 *   <.graphapi-menu>
 * </>
 *
 */
(function($) {
  $.fn.graphapi = function(options) {
    var opts = $.extend({}, $.graphapi.defaults, options);

    return this.each(function() {
      var $container = $(this);
      var options;
      options = $.extend({}, opts, options)
      $container.data('options', options);
      
      var $canvas = $('<canvas>').prependTo($container);
      $canvas
      .css('backgroundColor', opts.backgroundColor)
      .width(opts.width)
      .height(opts.height)
      .css('position', 'absolute')
      .css('top', 0)
      ;
      var canvas = $canvas.get(0);
      canvas.width = opts.width;
      canvas.height = opts.height;

      $container.children('.graphapi-nodes')
      .width(opts.width)
      .height(opts.height)
      .css('overflow', 'hidden')
      .css('position', 'relative')
      .css('top', 0)
      ;

      if (opts.menu) $.graphapi.menu($container);
      $.graphapi.init($container);

      $container.children('edges').children('edge').each(function() {
        var $this = $(this);
        var from = '#' + $this.attr('from');
        var to = '#' + $this.attr('to');
        $(from).css('border', '2px solid red');
        $(to).css('border', '2px solid green');
      });
    });

  };

  // Static members
  $.graphapi = {
    defaults : {
      width: 800,
      height: 600,
      backgroundColor: '#EEE',
      lineColor: '#000',
      arrowColor: '#222',
      menu : false,
      showForces : false,
      animate: true,
      initScale: 2,
      physics : {
        friction : 0.95,
        applyFriction : true,
        boundingBox : true,
        attractToCenter : true,
        coulombsLaw : true,
        hookesLaw : true,
        boxOverlap : true
      }
    },

    menu : function($container) {
      var m = $container.children('.graphapi-menu');
      if (m.size()==0) {
        m = $('<div>').addClass('graphapi-menu')
        .css('float', 'left')
        .css('backgroundColor', 'red')
        .css('z-index', 100)
        .css('position', 'absolute').css('top',0);
      ;
      }
      m.empty();
      
      $('<span>Menu</span>').appendTo(m);
      var l = $('<ul>').appendTo(m);

      var li = $('<li>');
      var cmd = $('<a href="#">Restart</a>').click(function(){
        $.graphapi.init($container);
        return false
      });
      cmd.appendTo(li);
      li.appendTo(l);

      var checks = {
        animate : {
          label: 'Animate',
          global: true
        },
        showForces : {
          label: 'Show forces',
          global: true
        },
        attractToCenter : {
          label: 'Actract to center'
        },
        boundingBox : {
          label: 'Bounding box'
        },
        coulombsLaw : {
          label: 'Coulombs law'
        },
        applyFriction : {
          label: 'Friction'
        },
        hookesLaw : {
          label: 'Hookes law'
        },
        boxOverlap : {
          label: 'Box overlap'
        }
      }

      $.each(checks, function(key, opts){
        li = $('<li>');
        cmd = $('<input type="checkbox" checked="checked" />').click(function(){
          if (opts.global) {
            $container.data('options')[key] = this.checked;
          }
          else {
            $container.data('options').physics[key] = this.checked;
          }
        });
        cmd.appendTo(li);
        $('<span>' + opts.label + '</span>').appendTo(li);
        li.appendTo(l);
      });

      m.hover(function(){
        l.slideDown();
      }, function(){
        l.slideUp();
      });
      l.slideUp();
      m.appendTo($container);

    },

    init : function($container){
      var opts =  $container.data('options');
      // setup nodes
      var $nodes = $container.children('.graphapi-nodes');
      $nodes.css('position', 'absolute').css('top',0)

      $nodes.children('.graphapi-node')
      .css('position', 'absolute')
      .each(function(index){
        var $this = $(this);
        $.graphapi.physics.init($this, (opts.initScale * Math.random()- opts.initScale/2)* opts.width, (opts.initScale *Math.random()- opts.initScale/ 2) * opts.height);
      }).children('.graphapi-body').hide().end().css('border','2px solid yellow');

      var mouseLog = function(e, o) {
        var position = o.position();
        var offset = o.offset();
        console.log(e.type + ": " + e.pageX + ", " + e.pageY);
        console.log("- position: " + position.left + "," + position.top);
        console.log("- offset:   " + offset.left + "," + offset.top);
        console.log("- rel:      " + (e.pageX - offset.left) + "," + (e.pageY - offset.top));
      }
      var getOffset = function(e, o) {
        var offset = o.offset();
        return { left : event.pageX - offset.left, top : event.pageY - offset.top};
      }
      // Add drag support
      $nodes.children('.graphapi-node')
      .removeClass('dragging')
      .mousemove( function(event){
        var $this = $(this);
        if ($this.hasClass('dragging')) {
          mouseLog(event,$this);
          var dragOffset = getOffset(event, $this);
          var oldOffset = $this.data('dragOffset');
          var dx = dragOffset.left - oldOffset.left;
          var dy = dragOffset.top - oldOffset.top;
          console.log("== " + dx + "," +dy);
          $this.css('left', $this.css('left')+ dx).css('top', $this.css('top') + dy);
        }
      })
      .mousedown(function(event){
        var $this = $(this);
        if ($this.addClass('dragging')) {
          mouseLog(event,$this);
          var offset = $this.offset();
          $this.data('dragOffset', getOffset(event, $this));
        }
      })
      .mouseup(function(event){
        var $this = $(this);
        if ($this.removeClass('dragging')) {
          mouseLog(event,$this);
        }
      });
    },

    canvas : {
      drawLine : function(ctx, physics1, physics2, color) {
        ctx.beginPath();
        ctx.strokeStyle = color;
        ctx.lineWidth = 1;
        ctx.moveTo(physics1.px, physics1.py);
        ctx.lineTo(physics2.px, physics2.py);
        ctx.stroke();
      },

      drawArrow : function(ctx, physics1, physics2, color) {
        var dirDx = physics2.px - physics1.px
        , dirDy = physics2.py - physics1.py;

        var p1x = physics1.px
        , p1y = physics1.py
        , ratio1 = Math.abs(dirDx) * physics1.dy - Math.abs(dirDy) * physics1.dx
        , temp
        ;


        if (ratio1 < 0) {
          // through top/bottom
          temp = physics1.dy * ((dirDy > 0) ? 1 : -1);
          p1x += temp * dirDx / dirDy;
          p1y += temp;
        }
        else if (ratio1 > 0) {
          // through left/right
          temp = physics1.dx * ((dirDx > 0) ? 1 : -1);
          p1y += temp * dirDy / dirDx;
          p1x += temp;
        }

        var p2x = physics2.px;
        var p2y = physics2.py;
        var ratio2 = Math.abs(dirDx) * physics2.dy - Math.abs(dirDy) * physics2.dx;
        if (ratio2 < 0) {
          // through top/bottom
          temp = physics2.dy * ((-dirDy > 0) ? 1 : -1);
          p2x += temp * dirDx / dirDy;
          p2y += temp;
        }
        else if (ratio2 > 0) {
          // through left/right
          temp = physics2.dx * ((-dirDx > 0) ? 1 : -1);
          p2y += temp * dirDy / dirDx;
          p2x += temp;
        }

        var w = Math.min(10.0, 5.0);
        var r2 = dirDx * dirDx + dirDy * dirDy;
        var r = Math.sqrt(r2);
        var forX = dirDx/r * w;
        var forY = dirDy/r * w;
        var leftX = -forY;
        var leftY = forX;
        var backX = forX * 0.5;
        var backY = forY * 0.5;

        ctx.beginPath();
        ctx.strokeStyle = color;
        ctx.fillStyle = color;
        ctx.moveTo(p1x,  p1y);
        ctx.lineTo(p2x,  p2y);
        ctx.lineTo(p2x - forX - leftX, p2y - forY - leftY);
        ctx.lineTo(p2x - forX + leftX, p2y - forY + leftY);
        ctx.lineTo(p2x ,  p2y);
        ctx.fill();
        ctx.stroke();
      },

      drawBox : function(ctx, physics) {
        ctx.beginPath();
        ctx.strokeStyle = '#000';
        ctx.strokeRect(physics.px - physics.dx, physics.py - physics.dy, 2* physics.dx, 2*physics.dy);
        ctx.stroke();
      }
    },
    
    physics : {
      init : function( $node, x, y) {
        var physics = {
          dx : $node.width() / 2,
          dy : $node.height() / 2,
          m : 1,
          q : 1,
          k : 1,
          px : x,
          py : y,
          vx : 0,
          vy : 0,
          ax : 0,
          ay : 0,
          fx : 0,
          fy : 0,
          id : $node.get(0).id
        }
        $node.data('physics', physics);
        $node.css('left', physics.px-physics.dx).css('top', physics.py-physics.dy);
      },

      applyForce : function ($node, fx, fy) {
        var physics = $node.data('physics');
        physics.fx += fx;
        physics.fy += fy;
      },

      updatePosition : function($node, dt, friction) {
        var physics = $node.data('physics');
        // F = m * a
        // da  = F / m * dt
        physics.ax = physics.fx / physics.m;
        physics.ay = physics.fy / physics.m;
        // dv = a * dt
        physics.vx += physics.ax * dt;
        physics.vy += physics.ay * dt;
        // Friction
        physics.vx *= friction;
        physics.vy *= friction;
        // dx = v * dt
        physics.px += physics.vx * dt;
        physics.py += physics.vy * dt;

        physics.fx = 0;
        physics.fy = 0;
        
        physics.dx = $node.width() / 2;
        physics.dy = $node.height() / 2;
      },

      attractToCenter : function (physics, center) {
        physics.fx += (center.px - physics.px) / 2;
        physics.fy += (center.py - physics.py) / 2;
      },

      /*
     * F = q1 * q2 / r2
     */
      coulombsLaw : function (physics1, physics2, options) {
        var rx = physics1.px - physics2.px;
        var ry = physics1.py - physics2.py;
        var r2 = rx * rx + ry * ry;
        if (r2 < 0.01) {
          r2 = 0.01;
        }
        var distance = Math.sqrt(r2);

        var fx = 80000 * (rx/distance) / r2
        var fy = 80000 * (ry/distance) / r2;

        physics1.fx += fx;
        physics1.fy += fy;

        physics2.fx -= fx;
        physics2.fy -= fy;
      },

      /*
     * F = k (u-u0);
     */
      hookesLaw : function (physics1, physics2, options) {
        var f;
        var u0 = 40;
        var rx = physics1.px - physics2.px;
        var ry = physics1.py - physics2.py;
        var r2 = rx * rx + ry * ry;
        var r = Math.sqrt(r2);

        if (r < 0.01) r = 0.01;
        var f = 4*(r-u0);
        
        var fx = f * rx/r;
        var fy = f * ry/r;
        

        physics1.fx+= -fx;
        physics1.fy+= -fy;

        physics2.fx+= fx;
        physics2.fy+= fy;

      },

      /*
       * Prevent particle form excaping from container
       */
      boundingBox : function(particle, container) {
        var abs = Math.abs;
        var dx = container.px - particle.px;
        var dy = container.py - particle.py;
        if (abs(dx) > (container.dx - particle.dx)) {
          particle.fx += dx;
        }
        if (abs(dy) > (container.dy - particle.dy)) {
          particle.fy += dy;
        }
      },

      /**
       * If 2 particles overlap use there
       * borders to calculate overlap forces
       * 
       */
      boxOverlap : function(physics1, physics2) {
        var range = 1.5;
        var strength = 400;
        var abs = Math.abs;
        var dx = physics2.px - physics1.px;
        var dy = physics2.py - physics1.py;

        var rx = dx / (physics1.dx + physics2.dx);
        var ry = dy / (physics1.dy + physics2.dy);

        if ((abs(rx) < range) && (abs(ry) < range)){
          if (abs(rx) < range) {
            physics1.fx -= strength;
            physics2.fx += strength;
          }
          if (abs(ry) < range) {
            physics1.fy -= strength;
            physics2.fy += strength;
          }
        }
      }

    },

    animate : function ($container) {
      var opts = $container.data('options');
      if (!opts.animate) return;

      var showForces = opts.showForces;

      var $nodes = $container.children('.graphapi-nodes');
      var width = $nodes.width();
      var height = $nodes.height();
      var lineColor = opts.lineColor;
      var arrowColor = opts.arrowColor;

      var attractToCenter = opts.physics.attractToCenter;
      var boundingBox = opts.physics.boundingBox;
      var coulombsLaw = opts.physics.coulombsLaw;
      var hookesLaw = opts.physics.hookesLaw;
      var boxOverlap = opts.physics.boxOverlap;

      var applyFriction = opts.physics.applyFriction;
      var friction = 1.00;
      if (applyFriction) {
        friction = opts.physics.friction;
      }

      var center =  {
        px: width / 2,
        py: height / 2,
        dx: width / 2,
        dy: height / 2
      };
      // Single point animation
      $nodes.children('.graphapi-node').each(function() {
        var node1 = this;
        var $node1 = $(node1);
        var physics1 = $node1.data('physics');
        if (attractToCenter) $.graphapi.physics.attractToCenter(physics1, center);
        if (boundingBox) $.graphapi.physics.boundingBox(physics1, center);

        // 2 point interaction
        $node1.nextAll('.graphapi-node').each(function() {
          var node2 = this;
          var $node2 = $(node2);
          if (node1.id != node2.id) {
            var physics2 = $node2.data('physics');
            if (coulombsLaw) $.graphapi.physics.coulombsLaw(physics1, physics2);
            if (boxOverlap) $.graphapi.physics.boxOverlap(physics1, physics2);
          }
        });
      });

      $container.children('edges').children().each(function() {
        var $this = $(this);
        var from = '#' + $this.attr('from');
        var to = '#' + $this.attr('to');
        if (hookesLaw) $.graphapi.physics.hookesLaw($(from).data('physics'), $(to).data('physics'));
      });

      // Adjust edges
      var canvas = $container.children('canvas').get(0)
      var ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      $container.children('edges').children().each(function() {
        var $this = $(this);
        var $from = $('#' + $this.attr('from'));
        var $to = $('#' + $this.attr('to'));
        $.graphapi.canvas.drawArrow(ctx, $from.data('physics'), $to.data('physics'), arrowColor);
      //$.graphapi.canvas.drawLine(ctx, $from.data('physics'),$to.data('physics'), lineColor);
      });
      // Update nodes
      $nodes.children('.graphapi-node').each(function() {
        var $node1 = $(this);
        var physics1 = $node1.data('physics');

        //$.graphapi.canvas.drawBox(ctx, physics1);
        if (showForces) {
          $.graphapi.canvas.drawLine(ctx, physics1, {
            px:physics1.px + physics1.fx,
            py:physics1.py + physics1.fy
          }, '#333');
        }
        $.graphapi.physics.updatePosition($node1, 0.020, friction);

        $node1.css('left', physics1.px - physics1.dx).css('top', physics1.py- physics1.dy);
      });
    }
  };
})(jQuery);

jQuery(document).ready(function(){
  var $ = jQuery;
  $('.graphapi').graphapi({
    menu:true
  });

  $('.graphapi').each(function(){
    var $container = $(this);
    $container.click(function() {
      $.graphapi.animate($container);
    });
  });

  setInterval(function() {
    $('.graphapi').each(function(){
      jQuery.graphapi.animate(jQuery(this));
    });
  }, 50);

});
