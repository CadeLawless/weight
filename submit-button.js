let submitButton = document.querySelector('.button');
let buttonText = document.querySelector('.tick');

const dumbbell = "<img src='images/dumbbell.png' height='50'>";

buttonText.innerHTML = "Submit";

submitButton.addEventListener('click', function() {

  if (buttonText.innerHTML !== "Submit") {
    buttonText.innerHTML = "Submit";
  } else if (buttonText.innerHTML === "Submit") {
    buttonText.innerHTML = dumbbell;
  }
  this.classList.toggle('button__circle');
});