document.querySelector("#dropdown-title h1").addEventListener("click", function(){
    this.classList.toggle("active-dropdown");
    document.querySelector("#dropdown-title-list").classList.toggle("hide-dropdown");
    document.querySelector("#dropdown-title-list").classList.toggle("show-dropdown");
});
window.addEventListener("click", function(e){
    if(document.querySelector("#dropdown-title-list").classList.contains("show-dropdown")){
        if(e.target != document.querySelector("#dropdown-title-list") && e.target != document.querySelector("#dropdown-title h1")){
            document.querySelector("#dropdown-title h1").classList.remove("active-dropdown");
            document.querySelector("#dropdown-title-list").classList.toggle("hide-dropdown");
            document.querySelector("#dropdown-title-list").classList.toggle("show-dropdown");
        }
    }
});