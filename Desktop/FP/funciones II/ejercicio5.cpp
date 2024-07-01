#include <iostream>
#include <cmath>
#include <complex>
using namespace std;
    void calcularRaices(double a, double b, double c,complex<double>& r1,complex<double>& r2, bool& sonReales) {
    double d = pow(b, 2) - 4 * a * c;
    if (d >= 0) {
        sonReales = true;
        r1 =complex<double>((-b +sqrt(d)) / (2 * a), 0);
        r2 =complex<double>((-b -sqrt(d)) / (2 * a), 0);
    } else {
        sonReales = false;
        r1 =complex<double>(-b / (2 * a),sqrt(-d) / (2 * a));
        r2 =complex<double>(-b / (2 * a),sqrt(-d) / (2 * a));
    }
}
int main() {
    double a, b, c;
    complex<double> r1, r2;
    bool sonReales;
    cout << "Ingrese el coeficiente a: ";cin>>a;
    cout << "Ingrese el coeficiente b: ";cin>>b;
    cout << "Ingrese el coeficiente c: ";cin>>c;
    calcularRaices(a, b, c, r1, r2, sonReales);
    if (sonReales) {
        if (r1 == r2) {
            cout<< "Las raices son reales e iguales."<<endl;
            cout<<"Raiz: "<<r1.real()<<endl;
        } else {
            cout<<"Las raíces son reales y diferentes."<<endl;
            cout<<"Raiz 1: "<<r1.real()<<endl;
            cout<<"Raiz 2: "<<r2.real()<<endl;
        }
    } else {
        cout<< "Las raices son complejas y conjugadas."<<endl;
        cout<< "Raiz 1: "<<r1<<endl;
        cout<< "Raiz 2: "<<r2<<endl;
    }
    return 0;
}
