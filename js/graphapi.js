Drupal.behaviors.graphapi = {
  attach : function(context, settings) {
    jQuery('.graphapi-node').draggable();
    jQuery('.graphapi-nodes').droppable({
      drop: function(event, ui) {
        var $this = jQuery(this);
        var draggable = ui.draggable;
        var position = draggable.position();
        var physics = draggable.data('physics');
        var $container = draggable.parents('.graphapi').first();
        jQuery.graphapi.physics.init(draggable, position.left+physics.dx, position.top+physics.dy);
        jQuery.graphapi.draw($container);
      }
    });
  }
}

