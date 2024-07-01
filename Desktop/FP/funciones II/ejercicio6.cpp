#include <iostream>
#include <cstdlib>
#include <ctime>
using namespace std;
string obtenerEleccionComputadora() {
    int eleccion=rand() % 3;
    if (eleccion==0) return "piedra";
    if (eleccion==1) return "papel";
    return "tijeras";
}
    string determinarGanador(string usuario,string computadora) {
    if (usuario == computadora) return "empate";
    if ((usuario == "piedra" && computadora == "tijeras") ||
        (usuario == "papel" && computadora == "piedra") ||
        (usuario == "tijeras" && computadora == "papel")) {
        return "usuario";
    } else {
        return "computadora";
    }
}
int main() {
    srand(time(0));
    int puntosUsuario = 0;
    int puntosComputadora = 0;
    while (puntosUsuario < 3 && puntosComputadora < 3) {
        string eleccionUsuario;
        cout << "Elija su jugada (piedra, papel o tijeras): ";cin >> eleccionUsuario;
        string eleccionComputadora = obtenerEleccionComputadora();
        cout << "La computadora elige: " << eleccionComputadora <<endl;

        string ganador = determinarGanador(eleccionUsuario, eleccionComputadora);
        if (ganador == "usuario") {
            cout << "¡Ganaste esta ronda!" <<endl;
            puntosUsuario++;
        } else if (ganador == "computadora") {
            cout<< "La computadora gana esta ronda."<<endl;
            puntosComputadora++;
        } else {
            cout<<"Esta ronda es un empate."<<endl;
        }
        cout<<"Puntos - Usuario: " << puntosUsuario << " Computadora: "<<puntosComputadora<<endl;
    }
    if (puntosUsuario == 3) {
        cout<<"¡Felicidades! Ganaste."<<endl;
    } else {
        cout<<"La computadora gano el juego. ¡Intenta otra vez!"<<endl;
    }
    return 0;
}
