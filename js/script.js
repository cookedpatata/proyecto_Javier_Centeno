document.addEventListener("DOMContentLoaded", () => {
    const tarjetas = document.querySelectorAll(".tarjeta-inmueble");

    tarjetas.forEach(tarjeta => {
        tarjeta.addEventListener("click", () => {
            const idInmueble = tarjeta.dataset.id;
            window.location.href = `detalles.php?id=${idInmueble}`;
        });
    });
});
