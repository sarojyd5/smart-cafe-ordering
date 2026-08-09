document.addEventListener("DOMContentLoaded", function () {

    const categoryButtons =
        document.querySelectorAll(".category-btn");

    const foodCards =
        document.querySelectorAll(".food-card");


    categoryButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            categoryButtons.forEach(function (btn) {
                btn.classList.remove("active");
            });

            this.classList.add("active");

            const selectedCategory =
                this.getAttribute("data-category");


            foodCards.forEach(function (card) {

                const foodCategory =
                    card.getAttribute("data-category");


                if (
                    selectedCategory === "all" ||
                    selectedCategory === foodCategory
                ) {

                    card.style.display = "block";

                } else {

                    card.style.display = "none";

                }

            });

        });

    });

});