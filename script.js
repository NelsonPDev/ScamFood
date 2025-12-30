const alergiasUsuario = [
	"leche",
	"lactosa",
	"trigo",
	"gluten",
	"huevo",
	"mani",
	"cacahuate",
	"soya"
];

function buscarProducto() {
	const codigo = document.getElementById("barcode").value.trim();
	const resultado = document.getElementById("resultado");

	if (codigo === "") {
		resultado.className = "advertencia";
		resultado.innerHTML = "<p>⚠️ Ingresa un código de barras</p>";
		return;
	}

	const url = `https://world.openfoodfacts.org/api/v0/product/${codigo}.json`;

	fetch(url)
		.then(res => res.json())
		.then(data => {

			// 🔍 DEPURACIÓN (puedes quitarlo después)
			console.log("RESPUESTA API:", data);

			// 🔧 CORRECCIÓN IMPORTANTE
			if (parseInt(data.status) !== 1) {
				resultado.className = "no-apto";
				resultado.innerHTML = "<p>❌ Producto no encontrado</p>";
				return;
			}

			const producto = data.product || {};
			const nombre = producto.product_name || "Nombre no disponible";
			const ingredientes = producto.ingredients_text || "";

			let mensaje = "";
			let clase = "";

			if (ingredientes === "") {
				mensaje = "⚠️ No se puede determinar si es seguro (ingredientes no disponibles)";
				clase = "advertencia";
			} else {
				const encontrados = alergiasUsuario.filter(alergia =>
					ingredientes.toLowerCase().includes(alergia)
				);

				if (encontrados.length > 0) {
					mensaje = `❌ NO APTO. Contiene: ${encontrados.join(", ")}`;
					clase = "no-apto";
				} else {
					mensaje = "✅ Producto seguro para consumir";
					clase = "seguro";
				}
			}

			resultado.className = clase;
			resultado.innerHTML = `
				<h2>${nombre}</h2>
				<p><strong>Ingredientes:</strong> ${ingredientes || "No disponibles"}</p>
				<p>${mensaje}</p>
			`;
		})
		.catch(error => {
			console.error("ERROR:", error);
			resultado.className = "advertencia";
			resultado.innerHTML = "<p>⚠️ Error al conectar con Open Food Facts</p>";
		});
}
