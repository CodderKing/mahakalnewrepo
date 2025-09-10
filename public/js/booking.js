document.addEventListener("DOMContentLoaded", function(){
    let selectedPackage = "";
    let selectedPackageName = "";
    let selectedPrice = "";

    document.querySelectorAll(".package-btn").forEach(btn => {
        btn.addEventListener("click", function(){
            selectedPackage = this.dataset.id;
            selectedPackageName = this.dataset.name;
            selectedPrice = this.dataset.price;
            document.getElementById("selectedPackageText").innerText = "Selected: " + this.dataset.name;
            document.getElementById("selectedPackage").value = selectedPackage;
            document.getElementById("selectedPrice").value = selectedPrice;
        });
    });

    const form = document.getElementById("bookingForm");

    form.addEventListener("submit", function(e){
        e.preventDefault();
        document.querySelector(".step1").classList.add("d-none");
        document.querySelectorAll(".step1-btn").forEach(btn => btn.classList.add("d-none"));

        // Fill confirmation step
        document.getElementById("confirmName").innerText = form.name.value;
        document.getElementById("confirmMobile").innerText = form.mobile.value;
        document.getElementById("confirmPackage").innerText = selectedPackageName;
        document.getElementById("confirmAmount").innerText = selectedPrice;

        document.querySelector(".step2").classList.remove("d-none");
        document.querySelectorAll(".step2-btn").forEach(btn => btn.classList.remove("d-none"));
    });

    document.getElementById("backBtn").addEventListener("click", function(){
        document.querySelector(".step2").classList.add("d-none");
        document.querySelectorAll(".step2-btn").forEach(btn => btn.classList.add("d-none"));

        document.querySelector(".step1").classList.remove("d-none");
        document.querySelectorAll(".step1-btn").forEach(btn => btn.classList.remove("d-none"));
    });

    document.getElementById("confirmPayBtn").addEventListener("click", function(){
        document.querySelector(".step2").classList.add("d-none");
        document.querySelectorAll(".step2-btn").forEach(btn => btn.classList.add("d-none"));
        document.querySelector(".step3").classList.remove("d-none");

        setTimeout(() => {
            document.querySelector(".step3").classList.add("d-none");
            document.querySelector(".step4").classList.remove("d-none");
            document.querySelectorAll(".step4-btn").forEach(btn => btn.classList.remove("d-none"));
        }, 2000);
    });
});
