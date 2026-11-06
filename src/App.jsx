import { dataContoh, dataKosong } from './data/contohHasil.js';
import HasilPanenPage from './pages/HasilPanenPage.jsx';

// Pratinjau: tambahkan ?data=kosong pada alamat untuk melihat keadaan kosong.
const kosong = new URLSearchParams(window.location.search).get('data') === 'kosong';

export default function App() {
    return <HasilPanenPage data={kosong ? dataKosong : dataContoh} />;
}
