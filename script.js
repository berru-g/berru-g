/*script by  Robert Gil Baptista repimpé*/
let text = document.getElementById('text');
let sat = document.getElementById('sat');
let stars = document.getElementById('stars');
let btn = document.getElementById('btn');
let mont = document.getElementById('mont');
let cosmos = document.getElementById('cosmos');
let forest = document.getElementById('forest');
let header = document.getElementById('header');

window.addEventListener('scroll', function() {
    let value = window.scrollY;

    text.style.top = 15 + value * +.2 + '%';
    stars.style.top = value * -1.5 + 'px';
    stars.style.left = value * 5 + 'px';
    sat.style.top = value * -1 + 'px';
    sat.style.left = value * -1 + 'px';
    btn.style.marginTop = value * 1.5 + 'px';
    mont.style.top = value * +.34 + 'px';
    cosmos.style.top = value * .25 + 'px';
    header.style.top = value * .5 + 'px';
})