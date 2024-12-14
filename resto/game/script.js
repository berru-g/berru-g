// Memory Game
// © 2014 Nate Wiley
// License -- MIT
// best in full screen, works on phones/tablets (min height for game is 500px..) enjoy ;)
// Follow me on Codepen

(function(){
	
	var Memory = {

		init: function(cards){
			this.$game = $(".game");
			this.$modal = $(".modal");
			this.$overlay = $(".modal-overlay");
			this.$restartButton = $("button.restart");
			this.cardsArray = $.merge(cards, cards);
			this.shuffleCards(this.cardsArray);
			this.setup();
		},

		shuffleCards: function(cardsArray){
			this.$cards = $(this.shuffle(this.cardsArray));
		},

		setup: function(){
			this.html = this.buildHTML();
			this.$game.html(this.html);
			this.$memoryCards = $(".card");
			this.paused = false;
     	this.guess = null;
			this.binding();
		},

		binding: function(){
			this.$memoryCards.on("click", this.cardClicked);
			this.$restartButton.on("click", $.proxy(this.reset, this));
		},
		// kinda messy but hey
		cardClicked: function(){
			var _ = Memory;
			var $card = $(this);
			if(!_.paused && !$card.find(".inside").hasClass("matched") && !$card.find(".inside").hasClass("picked")){
				$card.find(".inside").addClass("picked");
				if(!_.guess){
					_.guess = $(this).attr("data-id");
				} else if(_.guess == $(this).attr("data-id") && !$(this).hasClass("picked")){
					$(".picked").addClass("matched");
					_.guess = null;
				} else {
					_.guess = null;
					_.paused = true;
					setTimeout(function(){
						$(".picked").removeClass("picked");
						Memory.paused = false;
					}, 600);
				}
				if($(".matched").length == $(".card").length){
					_.win();
				}
			}
		},

		win: function(){
			this.paused = true;
			setTimeout(function(){
				Memory.showModal();
				Memory.$game.fadeOut();
			}, 1000);
		},

		showModal: function(){
			this.$overlay.show();
			this.$modal.fadeIn("slow");
		},

		hideModal: function(){
			this.$overlay.hide();
			this.$modal.hide();
		},

		reset: function(){
			this.hideModal();
			this.shuffleCards(this.cardsArray);
			this.setup();
			this.$game.show("slow");
		},

		// Fisher--Yates Algorithm -- https://bost.ocks.org/mike/shuffle/
		shuffle: function(array){
			var counter = array.length, temp, index;
	   	// While there are elements in the array
	   	while (counter > 0) {
        	// Pick a random index
        	index = Math.floor(Math.random() * counter);
        	// Decrease counter by 1
        	counter--;
        	// And swap the last element with it
        	temp = array[counter];
        	array[counter] = array[index];
        	array[index] = temp;
	    	}
	    	return array;
		},

		buildHTML: function(){
			var frag = '';
			this.$cards.each(function(k, v){
				frag += '<div class="card" data-id="'+ v.id +'"><div class="inside">\
				<div class="front"><img src="'+ v.img +'"\
				alt="'+ v.name +'" /></div>\
				<div class="back"><img src="https://img.icons8.com/?size=100&id=DLdHbRGIh8J6&format=png&color=000000"\
				alt="Codepen" /></div></div>\
				</div>';
			});
			return frag;
		}
	};

	var cards = [
		{
			name: "php",//cookie
			img: "https://img.icons8.com/?size=100&id=UKHU6VFfF1cf&format=png&color=000000",
			id: 1,
		},
		{
			name: "css3",//pizza
			img: "https://img.icons8.com/?size=100&id=oTyopyeAHiFU&format=png&color=000000",
			id: 2
		},
		{
			name: "html5",//nouilles chinoise
			img: "https://img.icons8.com/?size=100&id=kjPt9B8X89fb&format=png&color=000000",
			id: 3
		},
		{
			name: "jquery",//tacos
			img: "https://img.icons8.com/?size=100&id=d8xgvuLH67az&format=png&color=000000",
			id: 4
		}, 
		{
			name: "javascript",//eggs
			img: "https://img.icons8.com/?size=100&id=an1G4LtcC9mF&format=png&color=000000",
			id: 5
		},
		{
			name: "node",//pasteque
			img: "https://img.icons8.com/?size=100&id=qsamHkjVPQf3&format=png&color=000000",
			id: 6
		},
		{
			name: "photoshop",//gateau cerise
			img: "https://img.icons8.com/?size=100&id=istQWqDPsEXR&format=png&color=000000",
			id: 7
		},
		{
			name: "python",//chocolat
			img: "https://img.icons8.com/?size=100&id=tkUeNV2tFK39&format=png&color=000000",
			id: 8
		},
		{
			name: "rails",//salade
			img: "https://img.icons8.com/?size=100&id=GxuOUuJCLqtW&format=png&color=000000",
			id: 9
		},
		{
			name: "sass",//poulet
			img: "https://img.icons8.com/?size=100&id=rq1htNPCZYz0&format=png&color=000000",
			id: 10
		},
		{
			name: "sublime",//cookies croqué
			img: "https://img.icons8.com/?size=100&id=XMMUFFCHDBTT&format=png&color=000000",
			id: 11
		},
		{
			name: "wordpress",//steak
			img: "https://img.icons8.com/?size=100&id=ovpzFp00lmSi&format=png&color=000000",
			id: 12
		},
	];
    
	Memory.init(cards);


})();