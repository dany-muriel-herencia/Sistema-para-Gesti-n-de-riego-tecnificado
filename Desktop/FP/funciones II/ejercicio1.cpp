#include <iostream>
#include <cmath>
using namespace std;

int main() {
    double r;
    cout << "Ingrese el radio del circulo: ";cin >> r;
    // M_PI es igual a la constante pi
    double area = M_PI * pow(r, 2);
    cout << "El area del circulo con radio " << r << " es: "<<area<<endl;
    return 0;
}