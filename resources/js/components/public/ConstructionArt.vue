<script setup lang="ts">
/**
 * La ilustración del cartel de "En construcción": una grúa bajando un bloque de
 * contenido dentro de una página web a medio armar — la alegoría es la del sitio
 * mismo en obra, no la de un edificio.
 *
 * Va dibujada a mano en SVG y no como archivo: pesa nada, se ve nítida en
 * cualquier pantalla y toma los colores de la marca, así que sigue combinando si
 * la paleta cambia. Ver resources/js/pages/Public/UnderConstruction.vue.
 */
</script>

<template>
    <svg
        viewBox="0 0 520 380"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        role="img"
        aria-label="Una grúa colocando el último bloque de una página web en construcción"
    >
        <!-- Fondo: dos manchas suaves que despegan la escena del blanco. -->
        <ellipse cx="272" cy="196" rx="204" ry="162" fill="#E7F6FF" opacity="0.75" />
        <ellipse cx="372" cy="132" rx="96" ry="86" fill="#FFF1E6" opacity="0.45" />

        <!-- Guías de plano, para el aire de obra en marcha. -->
        <g stroke="#B0CFF0" stroke-width="2" stroke-linecap="round" opacity="0.7">
            <path d="M120 116h22M131 105v22" />
            <path d="M470 250h18M479 241v18" />
        </g>
        <g fill="#B0CFF0" opacity="0.75">
            <circle cx="462" cy="58" r="5" />
            <circle cx="492" cy="92" r="3.5" />
            <circle cx="96" cy="212" r="4" />
        </g>

        <!-- Sombras en el piso, antes que todo lo que se apoya en él. -->
        <ellipse cx="308" cy="337" rx="172" ry="8" fill="#D1DAE5" opacity="0.45" />
        <ellipse cx="84" cy="337" rx="48" ry="6" fill="#D1DAE5" opacity="0.5" />
        <path d="M26 337h468" stroke="#D1DAE5" stroke-width="3" stroke-linecap="round" />

        <!-- Grúa: base, mástil reticulado, pluma y contrapeso. -->
        <path d="M44 336l12-22h58l12 22z" fill="#D1DAE5" />
        <path d="M70 316l30-26-30-26 30-26-30-26 30-26-30-26 30-26-30-26" stroke="#B0CFF0" stroke-width="3" stroke-linejoin="round" opacity="0.9" />
        <path d="M70 316V96M100 316V96" stroke="#259ACF" stroke-width="5" stroke-linecap="round" />
        <path d="M85 54 36 82M85 54l315 28" stroke="#1E80AC" stroke-width="2.5" stroke-linecap="round" opacity="0.8" />
        <rect x="30" y="82" width="400" height="14" rx="4" fill="#259ACF" />
        <rect x="30" y="96" width="36" height="28" rx="4" fill="#CD6023" />
        <rect x="384" y="96" width="28" height="10" rx="3" fill="#1E80AC" />

        <!--
            El bloque cuelga del carro, así que la parte que se hamaca es cable,
            eslingas y bloque juntos: el origen de la rotación es el carro mismo.
        -->
        <g class="carga">
            <path d="M398 106v28" stroke="#7A7A7A" stroke-width="2.5" stroke-linecap="round" />
            <path d="M398 134l-46 16M398 134l46 16" stroke="#7A7A7A" stroke-width="2" stroke-linecap="round" opacity="0.8" />
            <rect x="340" y="150" width="116" height="42" rx="9" fill="#FFF1E6" stroke="#CD6023" stroke-width="2.5" />
            <rect x="354" y="163" width="56" height="7" rx="3.5" fill="#CD6023" opacity="0.5" />
            <rect x="354" y="176" width="82" height="7" rx="3.5" fill="#CD6023" opacity="0.28" />
        </g>

        <!-- La página en obra. -->
        <rect x="140" y="206" width="330" height="124" rx="14" fill="#FFFFFF" stroke="#D1DAE5" stroke-width="2.5" />
        <path d="M140 236h330" stroke="#D1DAE5" stroke-width="2" />
        <g>
            <circle cx="161" cy="221" r="4.5" fill="#CD6023" opacity="0.55" />
            <circle cx="176" cy="221" r="4.5" fill="#B0CFF0" />
            <circle cx="191" cy="221" r="4.5" fill="#D1DAE5" />
        </g>

        <!-- Bloque de portada ya colocado, con su marca de foto. -->
        <rect x="154" y="250" width="166" height="66" rx="9" fill="#B0CFF0" opacity="0.55" />
        <circle cx="288" cy="268" r="9" fill="#FFF1E6" />
        <path d="M162 312l38-40 27 29 20-21 39 32z" fill="#259ACF" opacity="0.5" />

        <!-- El hueco que espera al bloque que baja la grúa. -->
        <rect
            x="340"
            y="250"
            width="116"
            height="42"
            rx="9"
            fill="#FFF1E6"
            fill-opacity="0.5"
            stroke="#CD6023"
            stroke-width="2.5"
            stroke-dasharray="8 8"
            opacity="0.65"
        />
        <rect x="340" y="300" width="94" height="7" rx="3.5" fill="#D1DAE5" />
        <rect x="340" y="311" width="62" height="7" rx="3.5" fill="#D1DAE5" opacity="0.7" />

        <!-- Cono de obra. -->
        <path d="M497 300l13 34h-26z" fill="#CD6023" />
        <path d="M492 317h10" stroke="#FFF1E6" stroke-width="4" stroke-linecap="round" />
        <rect x="480" y="331" width="34" height="6" rx="3" fill="#AB4F1B" />
    </svg>
</template>

<style scoped>
/*
 * El bloque se hamaca apenas —dos grados— colgado del carro de la pluma. Lo
 * suficiente para que la escena esté viva sin robarle la atención al texto.
 */
.carga {
    transform-origin: 398px 106px;
    animation: hamaca 5s ease-in-out infinite;
}

@keyframes hamaca {
    0%,
    100% {
        transform: rotate(-2deg);
    }

    50% {
        transform: rotate(2deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .carga {
        animation: none;
    }
}
</style>
