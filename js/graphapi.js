function renderGraph(canvasId, links) {
	var graph = new Graph();
	var n = {};
	var nodes = {};
	jQuery(canvasId).parent().children("div").each(function(index) {
		var domId = jQuery(this).get()[0].id;
		var o = jQuery("#" + domId);
		nodes[domId] = {
			"dom_id" : domId,
			"width" : o.width(),
			"height" : o.height()
		};
	});
	for ( var i in nodes) {
		n[i] = graph.newNode(nodes[i]);
	}

	for ( var i in links) {
		var l = links[i];
		var from = n[l.from];
		var to = n[l.to];
		var data = l.data;
		// The links may contains invalid node refs
		if (from == undefined || to == undefined) {
			continue
		}
		var e = graph.newEdge(from, to);
		var colors = [ '#00A0B0', '#6A4A3C', '#CC333F', '#EB6841', '#EDC951',
				'#7DBE3C', '#000000' ];
		if (data !== undefined && data.color !== undefined) {
			color = data.color;
		} else {
			color = colors[Math.floor(Math.random() * colors.length)];
		}
		e.data.stroke = color;
	}
	// -----------
	var canvas = jQuery(canvasId).get(0);
	var ctx = canvas.getContext("2d");
	var width = jQuery(canvasId).width();
	var height = jQuery(canvasId).height();
	var zoom = 45.0;

	var layout = new Layout.ForceDirected(graph, 100.0, 100.0, 0.5);

	// calculate bounding box of graph layout.. with ease-in
	var currentBB = layout.getBoundingBox();
	var targetBB = {
		bottomleft : new Vector(-10, -10),
		topright : new Vector(10, 10)
	};

	setInterval(function() {
		targetBB = layout.getBoundingBox();
		// current gets 20% closer to target every iteration
			currentBB = {
				bottomleft : currentBB.bottomleft.add(targetBB.bottomleft
						.subtract(currentBB.bottomleft).divide(10)),
				topright : currentBB.topright.add(targetBB.topright.subtract(
						currentBB.topright).divide(10))
			};
		}, 100);

	// convert to/from screen coordinates
	toScreen = function(p) {
		var size = currentBB.topright.subtract(currentBB.bottomleft);
		var sx = p.subtract(currentBB.bottomleft).divide(size.x).x * width;
		var sy = p.subtract(currentBB.bottomleft).divide(size.y).y * height;

		return new Vector(sx, sy);
	};

	fromScreen = function(s) {
		var size = currentBB.topright.subtract(currentBB.bottomleft);
		var px = (s.x / width) * size.x + currentBB.bottomleft.x;
		var py = (s.y / height) * size.y + currentBB.bottomleft.y;

		return new Vector(px, py);
	};

	// half-assed drag and drop
	var selected = null;
	var nearest = null;
	var dragged = null;

	jQuery(canvasId).mousedown(function(e) {
		var pos = jQuery(this).offset();
		var p = fromScreen( {
			x : e.pageX - pos.left,
			y : e.pageY - pos.top
		});
		selected = nearest = dragged = layout.nearest(p);

		dragged.oldm = dragged.point.m;
		dragged.olddata = dragged.node.data;
		// deep copy
			dragged.node.data = jQuery.extend(true, {}, dragged.node.data);
			dragged.point.m = 1000.0;
			renderer.start();
		});

	jQuery(canvasId).mousemove(function(e) {
		var pos = jQuery(this).offset();
		var p = fromScreen( {
			x : e.pageX - pos.left,
			y : e.pageY - pos.top
		});
		nearest = layout.nearest(p);

		if (dragged !== null) {
			dragged.point.p.x = p.x;
			dragged.point.p.y = p.y;
		}

		renderer.start();
	});

	jQuery(window).bind('mouseup', function(e) {
		if (dragged !== null) {
			dragged.node.data = dragged.olddata;
		}
		dragged = null;
	});

	var boxWidth = 100;
	var boxHeight = 58;

	var renderer = new Renderer(
			1,
			layout,
			function clear() {
				ctx.clearRect(0, 0, width, height);
				ctx.lineWidth = 0.1;
				ctx.strokeStyle = "rgba(0,0,0,0.5)";
			},
			function drawEdge(edge, p1, p2) {
//				if (p1.x === Number.NaN || p2.x === Number.NaN)
//					return;
				var margin = 5;
				var x1 = toScreen(p1).x;
				var y1 = toScreen(p1).y;
				var x2 = toScreen(p2).x;
				var y2 = toScreen(p2).y;
				var direction = new Vector(x2 - x1, y2 - y1);
				var normal = direction.normal().normalise();
				var from = graph.getEdges(edge.source, edge.target);
				var to = graph.getEdges(edge.target, edge.source);
				var total = from.length + to.length;
				var n = from.indexOf(edge);

				var spacing = 6.0;

				// Figure out how far off centre the line should be drawn
				var offset = normal.multiply(-((total - 1) * spacing) / 2.0
						+ (n * spacing));
				var s1 = toScreen(p1).add(offset);
				var s2 = toScreen(p2).add(offset);
				var width = edge.target.data.width;
				var height = edge.target.data.height;
				var intersection = intersect_line_box(s1, s2, {
					x : x2 - width / 2.0 - margin,
					y : y2 - height / 2.0 - margin
				}, width + 2 * margin, height + 2 * margin);

				if (!intersection)
					intersection = s2;

				var stroke = typeof (edge.data.stroke) !== 'undefined' ? edge.data.stroke
						: "#000000";
				ctx.strokeStyle = stroke;
				var arrowWidth;
				var arrowLength;
				if (selected !== null
						&& (selected.node === edge.source || selected.node === edge.target)) {
					ctx.lineWidth = 5;
					arrowWidth = 7;
					arrowLength = 10;
				} else {
					ctx.lineWidth = 2;
					arrowWidth = 3;
					arrowLength = 8;
				}

				// line
				var lineEnd = intersection.subtract(direction.normalise()
						.multiply(arrowLength * 0.5));
				ctx.beginPath();
				ctx.moveTo(s1.x, s1.y);
				ctx.lineTo(lineEnd.x, lineEnd.y);
				ctx.stroke();
				// arrow
				ctx.save();
				ctx.fillStyle = stroke;
				ctx.translate(intersection.x, intersection.y);
				ctx.rotate(Math.atan2(y2 - y1, x2 - x1));
				ctx.beginPath();
				ctx.moveTo(-arrowLength, arrowWidth);
				ctx.lineTo(0, 0);
				ctx.lineTo(-arrowLength, -arrowWidth);
				ctx.lineTo(-arrowLength * 0.8, -0);
				ctx.closePath();
				ctx.fill();
				ctx.restore();
			},
			function drawNode(node, p) {
				var fill = typeof (node.data.fill) !== 'undefined' ? node.data.fill
						: "#FFFFFF";
				var s = toScreen(p);
				// box edge
				if (selected !== null && selected.node === node) {
					ctx.fillStyle = "#F2EFD9";
					ctx.strokeStyle = "#000000";
					ctx.lineWidth = 2.5;
				} else if (nearest !== null && nearest.node === node) {
					ctx.fillStyle = "#FFFFFF";
					ctx.strokeStyle = "#000000";
					ctx.lineWidth = 3;
				} else {
					ctx.fillStyle = "#FFFFFF";
					ctx.strokeStyle = "#000000";
					ctx.lineWidth = 1.5;
				}
				var margin = 5;
				var id;
				var width = 200;
				var height = 100;
				if (typeof (node.data.dom_id) !== 'undefined') {
					id = "#" + node.data.dom_id;
					width = jQuery(id).width() + 2 * margin;
					height = jQuery(id).height() + 2 * margin;
				} else {
					width = boxWidth + 2 * margin;
					height = boxHeight + 2 * margin;
				}
				
				if (typeof (node.data.color) !== 'undefined') {
					ctx.strokeStyle = node.data.color;
				}
				ctx.save();
				ctx.shadowBlur = 5;
				ctx.shadowColor = '#000000';
				ctx.fillRect(s.x - width / 2.0, s.y - height / 2.0, width,
						height);
				ctx.restore();
				ctx.strokeRect(s.x - width / 2.0, s.y - height / 2.0, width,
						height);
				// clip drawing within rectangle
				ctx.save()
				ctx.beginPath();
				ctx.rect(s.x - width / 2.0 + 2, s.y - height / 2.0 + 2,
						width - 4, height - 4);
				ctx.clip();
				if (typeof (node.data.dom_id) !== 'undefined') {
					var margin = 5;
					var left = s.x + margin - width / 2.0;
					var top = s.y + margin - height / 2.0;
					jQuery(id).css('top', top);
					jQuery(id).css('left', left);
				}
				ctx.restore()
			});

	renderer.start();

	// helpers for figuring out where to draw arrows
	function intersect_line_line(p1, p2, p3, p4) {
		var denom = ((p4.y - p3.y) * (p2.x - p1.x) - (p4.x - p3.x)
				* (p2.y - p1.y));

		// lines are parallel
		if (denom === 0) {
			return false;
		}

		var ua = ((p4.x - p3.x) * (p1.y - p3.y) - (p4.y - p3.y) * (p1.x - p3.x))
				/ denom;
		var ub = ((p2.x - p1.x) * (p1.y - p3.y) - (p2.y - p1.y) * (p1.x - p3.x))
				/ denom;

		if (ua < 0 || ua > 1 || ub < 0 || ub > 1) {
			return false;
		}

		return new Vector(p1.x + ua * (p2.x - p1.x), p1.y + ua * (p2.y - p1.y));
	}

	function intersect_line_box(p1, p2, p3, w, h) {
		var tl = {
			x : p3.x,
			y : p3.y
		};
		var tr = {
			x : p3.x + w,
			y : p3.y
		};
		var bl = {
			x : p3.x,
			y : p3.y + h
		};
		var br = {
			x : p3.x + w,
			y : p3.y + h
		};

		var result;
		if (result = intersect_line_line(p1, p2, tl, tr)) {
			return result;
		} // top
		if (result = intersect_line_line(p1, p2, tr, br)) {
			return result;
		} // right
		if (result = intersect_line_line(p1, p2, br, bl)) {
			return result;
		} // bottom
		if (result = intersect_line_line(p1, p2, bl, tl)) {
			return result;
		} // left

		return false;
	}
}