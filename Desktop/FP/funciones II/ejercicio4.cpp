#include <iostream>
#include <cmath>
#include <complex>
using namespace std;
int main() {
    double a, b, c;
    cout << "Ingrese el coeficiente a: ";cin >> a;
    cout << "Ingrese el coeficiente b: ";cin >> b;
    cout << "Ingrese el coeficiente c: ";cin >> c;
    double d =pow(b, 2) - 4 * a * c;

    if (d > 0) {
        double r1 = (-b + sqrt(d)) / (2 * a);
        double r2 = (-b - sqrt(d)) / (2 * a);
        cout<< "Las raices son reales y diferentes."<<endl;
        cout<< "Raiz 1: " << r1 <<endl;
        cout<< "Raiz 2: " << r2 <<endl;
    } else if (d == 0) {
        double raiz = -b / (2 * a);
        cout<< "Las raices son reales e iguales." <<endl;
        cout<< "Raiz: " << raiz <<endl;
    } else {
        complex<double> r1 =complex<double>(-b,-sqrt(-d)) / (2.0 * a);
        complex<double> r2 =complex<double>(-b,-sqrt(-d)) / (2.0 * a);
        cout<< "Las raices son complejas y conjugadas." <<endl;
        cout<< "Raiz 1: " << r1 <<endl;
        cout<< "Raiz 2: " << r2 <<endl;
    }
    return 0;
}