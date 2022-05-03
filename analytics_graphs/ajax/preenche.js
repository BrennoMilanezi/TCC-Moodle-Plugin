//	Variável que receberá o objeto XMLHttpRequest
var ret;

// 4º Parametro recebe uma função, caso receba alguma outra coisa se não função, o valor dele sera ignorado
function preencheCampo(campo, valor, tipo, callback) {

	// Verificar o Browser
	// Firefox, Google Chrorme, Safari e outros
	if(window.XMLHttpRequest) {
		ret	= new XMLHttpRequest();
	}
	// Internet Explorer
	else if(window.ActiveXObject) {
		ret = new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	// Aqui vão os valores, caso haja mais de um, e o nome do campo que pediu a requisição.
	var url2 = "../analytics_graphs/ajax/preenche.php?campo="+campo+"&valor="+valor+"&tipo="+tipo;
	
	// Chamada do método open para processar a requisição
	ret.open("Get", url2, true); 
    // Quando o objeto recebe o retorno, chamamos a função callback();
	ret.onreadystatechange = function() {
		
		// Exibindo mensagem de carregar
		if(ret.readyState == 1 || ret.readyState == 3) {
			document.getElementById(''+ campo +'').innerHTML = '<font color="black">Buscando...</font>';
		}
	
		// Verifica se o Ajax realizou todas as operações corretamente (essencial)
		if(ret.readyState == 4 && ret.status == 200) {
	        // Resposta retornada pelo executor.php
			var resposta2 = ret.responseText;
			
			// Abaixo colocamos a resposta na div do campo que fez a requisição
			document.getElementById(''+ campo +'').innerHTML = resposta2;
			
			//Se o ajax tiver retornado realiza a função passada no 4º parametro
			if (callback && typeof (callback) === "function") {
				callback();
			}
		}
	
	}
	
	ret.send(null);
	
}