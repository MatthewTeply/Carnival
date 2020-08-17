$(document).ready(function(){
$('#open-form').on('click', function(e) {
      $('.form-around').toggleClass("toggled");
    });
});

$(document).ready(function(){
$('#login-btn').on('click', function(e) {
      $('#login-btn .text, #login-btn .loader, #login-btn').toggleClass("toggled");
    });
});

function getRandomInt(min, max) {
  return Math.floor(Math.random() * (max - min)) + min;
}

function particlesInit() {
	var generator = document.getElementById("particleGenerator");
	var particleCount = 200;
	for (var i = 0; i < particleCount; i++) {
		var size = getRandomInt(2, 6);
		var n = '<div class="particle" style="top:' + getRandomInt(15, 95) + '%; left:' + getRandomInt(5,95) + '%; width:'
		+ size + 'px; height:' + size + 'px; animation-delay:' + (getRandomInt(0,30)/10) + 's; background-color:rgba('
		+ getRandomInt(235, 255) + ',' + getRandomInt(235, 255) + ',' + getRandomInt(235, 255) + ',' + (getRandomInt(235, 255)/10) + ');"></div>';
		var node = document.createElement("div");
		node.innerHTML = n;
		generator.appendChild(node);
	}
}

particlesInit();