#include <iostream>
#include <cstdlib>
#include <ctime>
using namespace std;
int main() {
    srand(time(0));
     int c, m;
    cout<<"Ingrese la cantidad de numeros aleatorios a generar: ";cin>>c;
    cout<<"Ingrese el valor maximo permitido: ";cin>>m;
    cout<<"Numeros aleatorios generados: "<<endl;
    for (int i = 0; i < c; ++i) {
        int numero_aleatorio =rand() % (m + 1);
        cout << numero_aleatorio << " ";
    }

    cout<<endl;

    return 0;
}